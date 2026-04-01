<?php
/**
 * LoyalLoop Prediction Engine v2
 * Weighted Moving Average + Trend Detection
 * Runs silently in the background.
 * Falls back to smart defaults when no sales history exists.
 *
 * Algorithm:
 *   1. Pull last 6 months of sale_items data grouped by month
 *   2. Apply WMA with weights [1,2,3] on the 3 most recent months
 *   3. Compute linear trend (slope) to adjust for growth/decline
 *   4. If no history: use reorder_level or 20 as a safe default
 *   5. Return: predicted_qty, reorder_qty, confidence, avg_monthly_sales
 */

function predictDemand($product_id, $shop_id, $conn) {

    // ── Pull last 6 months of sales for this product ──
    $sql = "SELECT
                YEAR(s.sale_date)  AS yr,
                MONTH(s.sale_date) AS mo,
                SUM(si.quantity)   AS total_qty
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            WHERE si.product_id = ?
              AND s.shop_id     = ?
              AND s.sale_date  >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY YEAR(s.sale_date), MONTH(s.sale_date)
            ORDER BY yr ASC, mo ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $product_id, $shop_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $monthly_data = [];
    while ($row = $result->fetch_assoc()) {
        $monthly_data[] = (int)$row['total_qty'];
    }

    $count = count($monthly_data);

    // ── FALLBACK: No sales history ──
    // Silently use the shopkeeper's own reorder_level as the baseline.
    // If reorder_level is not set, default to 20 units (safe universal minimum).
    if ($count === 0) {
        $fb = $conn->query(
            "SELECT reorder_level FROM products WHERE id='$product_id' LIMIT 1"
        )->fetch_assoc();
        $rl           = (int)($fb['reorder_level'] ?? 0);
        $fallback_qty = $rl > 0 ? $rl : 20;
        return [
            'predicted_qty'     => $fallback_qty,
            'confidence'        => 'none',    // engine knows there's no history
            'avg_monthly_sales' => 0,
            'months_of_data'    => 0,
        ];
    }

    // ── WEIGHTED MOVING AVERAGE ──
    // Weights: oldest→ 1, middle→ 2, most recent→ 3
    $avg    = array_sum($monthly_data) / $count;
    $window  = array_slice($monthly_data, -3);
    $weights = [1, 2, 3];
    $w_sum = $w_total = 0;
    for ($i = 0; $i < count($window); $i++) {
        $w       = $weights[$i] ?? 1;
        $w_sum  += $window[$i] * $w;
        $w_total += $w;
    }
    $wma = $w_total > 0 ? ($w_sum / $w_total) : $avg;

    // ── TREND ADJUSTMENT ──
    // Compare first-half vs second-half average; dampen by 0.3 to avoid over-ordering
    $trend = 0;
    if ($count >= 4) {
        $mid   = intval($count / 2);
        $first = array_slice($monthly_data, 0, $mid);
        $last  = array_slice($monthly_data, $mid);
        $slope = (array_sum($last) / count($last)) - (array_sum($first) / count($first));
        $trend = $slope * 0.3;
    }

    $predicted = max(0, (int)round($wma + $trend));

    // ── CONFIDENCE ──
    if      ($count >= 5) $confidence = 'high';
    elseif  ($count >= 3) $confidence = 'medium';
    else                  $confidence = 'low';

    return [
        'predicted_qty'     => $predicted,
        'confidence'        => $confidence,
        'avg_monthly_sales' => round($avg, 1),
        'months_of_data'    => $count,
    ];
}


/**
 * getAllProductPredictions()
 * Returns Group A (assigned suppliers) and Group B (unassigned).
 * Reorder qty uses a two-tier rule:
 *   Tier 1 — stock ≤ reorder_level  →  MUST restock regardless of AI
 *   Tier 2 — stock > reorder_level  →  restock only if AI predicts shortfall
 */
function getAllProductPredictions($shop_id, $conn) {

    $sql = "SELECT p.id, p.name, p.stock_qty, p.reorder_level, p.category,
                   p.default_supplier_id,
                   s.name           AS supplier_name,
                   s.whatsapp_number AS supplier_wa
            FROM products p
            LEFT JOIN suppliers s ON p.default_supplier_id = s.id
            WHERE p.shop_id = ? AND p.status = 'active'
            ORDER BY s.name ASC, p.name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $group_a = [];
    $group_b = [];

    while ($row = $result->fetch_assoc()) {
        $pred      = predictDemand($row['id'], $shop_id, $conn);
        $stock     = (int)$row['stock_qty'];
        $rl        = (int)($row['reorder_level'] ?: 0);
        $predicted = (int)$pred['predicted_qty'];

        // ── Two-tier reorder logic ──
        if ($rl > 0 && $stock <= $rl) {
            // Tier 1: Below reorder threshold — must reorder
            // Order quantity = predicted demand (covers one cycle),
            //   or reorder_level if prediction isn't available
            $order_qty = max($predicted, $rl);
            $needs_restock = true;
        } elseif ($predicted > $stock) {
            // Tier 2: AI foresees a shortfall
            $order_qty     = $predicted - $stock;
            $needs_restock = true;
        } else {
            // Well stocked
            $order_qty     = 0;
            $needs_restock = false;
        }

        $item = [
            'product_id'    => $row['id'],
            'name'          => $row['name'],
            'stock_qty'     => $stock,
            'reorder_level' => $rl,
            'category'      => $row['category'],
            'predicted_qty' => $predicted,
            'reorder_qty'   => $order_qty,
            'needs_restock' => $needs_restock,
            'confidence'    => $pred['confidence'],
            'avg_sales'     => $pred['avg_monthly_sales'],
            'months_data'   => $pred['months_of_data'],
            'supplier_id'   => $row['default_supplier_id'],
            'supplier_name' => $row['supplier_name'],
            'supplier_wa'   => $row['supplier_wa'],
        ];

        if ($row['default_supplier_id']) {
            $sid = $row['default_supplier_id'];
            if (!isset($group_a[$sid])) {
                $group_a[$sid] = [
                    'supplier_id'   => $sid,
                    'supplier_name' => $row['supplier_name'],
                    'supplier_wa'   => $row['supplier_wa'],
                    'products'      => [],
                ];
            }
            $group_a[$sid]['products'][] = $item;
        } else {
            $group_b[] = $item;
        }
    }

    return ['group_a' => $group_a, 'group_b' => $group_b];
}
