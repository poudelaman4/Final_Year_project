<?php
// student/order_details.php - Shows details of a specific order

date_default_timezone_set('Asia/Kathmandu');
session_start();

// --- REQUIRE STUDENT LOGIN ---
if (!isset($_SESSION['student_id']) || empty($_SESSION['student_id'])) {
    $_SESSION['login_error_message'] = "You need to log in to view order details.";
    header('Location: ../login.php');
    exit();
}
$current_student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_full_name'] ?? 'Student';

require_once '../includes/db_connection.php';
include '../includes/packages.php';

$transaction_id_from_url = null;
$transaction_details = null;
$order_items = [];
$error_message = "";

if (isset($_GET['txn_id']) && filter_var($_GET['txn_id'], FILTER_VALIDATE_INT)) {
    $transaction_id_from_url = (int)$_GET['txn_id'];
} else {
    $error_message = "Invalid transaction ID specified.";
}

if ($transaction_id_from_url && empty($error_message)) {
    // Fetch transaction details and verify it belongs to the current student
    $sqlTransaction = "SELECT txn_id, formatted_id, total_amount, status, transaction_time
                        FROM transaction
                        WHERE txn_id = ? AND student_id = ?";
    if ($stmtTrans = mysqli_prepare($link, $sqlTransaction)) {
        mysqli_stmt_bind_param($stmtTrans, "ii", $transaction_id_from_url, $current_student_id);
        mysqli_stmt_execute($stmtTrans);
        $resultTrans = mysqli_stmt_get_result($stmtTrans);
        $transaction_details = mysqli_fetch_assoc($resultTrans);
        mysqli_stmt_close($stmtTrans);

        if (!$transaction_details) {
            $error_message = "Order not found or you do not have permission to view it.";
            $transaction_details = null; // Ensure it's null if not found
        } else {
            // Fetch order items
            $sqlItems = "SELECT ti.quantity, ti.unit_price, f.name AS food_name, f.image_path
                          FROM transaction_item ti
                          JOIN food f ON ti.food_id = f.food_id
                          WHERE ti.txn_id = ?";
            if ($stmtItems = mysqli_prepare($link, $sqlItems)) {
                mysqli_stmt_bind_param($stmtItems, "i", $transaction_id_from_url);
                mysqli_stmt_execute($stmtItems);
                $resultItems = mysqli_stmt_get_result($stmtItems);
                while ($row = mysqli_fetch_assoc($resultItems)) {
                    $order_items[] = $row;
                }
                mysqli_stmt_close($stmtItems);

                if (empty($order_items) && $transaction_details) { // Should not happen if transaction exists
                    $error_message = "No items found for this order. This might indicate an issue.";
                }
            } else {
                $error_message = "Error fetching order items details.";
                error_log("DB Error (order_details.php - items): " . mysqli_error($link));
            }
        }
    } else {
        $error_message = "Error fetching transaction details.";
        error_log("DB Error (order_details.php - transaction): " . mysqli_error($link));
    }
}

include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - <?php echo $transaction_details ? htmlspecialchars($transaction_details['formatted_id']) : 'Error'; ?></title>
    <style>
        .container { max-width: 800px; margin: 2rem auto; padding: 0 1rem; }
        .order-summary, .items-list { 
            background-color: #fff !important; 
            color: #000 !important;
            padding: 1.5rem; 
            border-radius: 0.5rem; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            margin-bottom: 1.5rem; 
        }
        .items-list table { width: 100%; border-collapse: collapse; }
        .items-list th, .items-list td { 
            padding: 0.75rem; 
            border-bottom: 1px solid #e2e8f0; 
            text-align: left; 
            color: #000 !important;
        }
        .items-list th { 
            background-color: #f7fafc !important; 
            font-weight: 600; 
            color: #000 !important;
        }
        .items-list img { max-width: 60px; height: auto; border-radius: 0.25rem; margin-right: 0.75rem; }
        .item-total { font-weight: bold; }
        .grand-total { font-size: 1.25rem; font-weight: bold; margin-top: 1rem; text-align: right; }
        .error-message { 
            text-align: center; 
            color: #e53e3e !important; 
            background-color: #fff0f3 !important; 
            padding: 1rem; 
            border: 1px solid #e53e3e; 
            border-radius: 0.5rem; 
        }

        /* Force light mode for content sections */
        .content-section {
            background-color: #fff !important;
            color: #000 !important;
        }
        .content-section * {
            color: #000 !important;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-black">Order Details</h1>
            <a href="order_history.php" class="text-indigo-600 hover:text-indigo-800">&larr; Back to Order History</a>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php elseif ($transaction_details && !empty($order_items)): ?>
            <section class="order-summary content-section">
                <h2 class="text-xl font-semibold mb-4 text-black">Order Summary</h2>
                <p class="text-black"><strong>Order ID:</strong> <?php echo htmlspecialchars($transaction_details['formatted_id']); ?></p>
                <p class="text-black"><strong>Date Placed:</strong>
                    <?php
                    try {
                        $date = new DateTime($transaction_details['transaction_time'], new DateTimeZone('UTC'));
                        $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
                        echo $date->format('F j, Y, g:i A');
                    } catch (Exception $e) {
                        echo htmlspecialchars($transaction_details['transaction_time']);
                    }
                    ?>
                </p>
                <p class="text-black"><strong>Status:</strong> <span class="font-semibold"><?php echo ucfirst(htmlspecialchars($transaction_details['status'])); ?></span></p>
                <p class="grand-total text-black"><strong>Grand Total: ₹<?php echo number_format($transaction_details['total_amount'], 2); ?></strong></p>
            </section>

            <section class="items-list content-section">
                <h2 class="text-xl font-semibold mb-4 text-black">Items Ordered</h2>
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-900 uppercase tracking-wider">Item</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-900 uppercase tracking-wider">Unit Price</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-900 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-900 uppercase tracking-wider">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php if (!empty($item['image_path']) && file_exists('../' . $item['image_path'])): ?>
                                                <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['food_name']); ?>" class="w-12 h-12 object-cover rounded mr-3">
                                            <?php else: ?>
                                                <div class="w-12 h-12 bg-gray-300 rounded mr-3 flex items-center justify-center text-gray-500 text-xs">No Image</div>
                                            <?php endif; ?>
                                            <span class="text-sm font-medium text-black"><?php echo htmlspecialchars($item['food_name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-black">₹<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-black"><?php echo $item['quantity']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right item-total text-black">
                                        ₹<?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php elseif ($transaction_details && empty($order_items) && empty($error_message)): ?>
             <div class="error-message">
                <p>No items were found for this order (ID: <?php echo htmlspecialchars($transaction_details['formatted_id']); ?>). Please contact support if you believe this is an error.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
    <?php
    if (isset($link) && $link) {
        mysqli_close($link);
    }
    ?>
</body>
</html>