<!-- Explain why and how you would use transactions in PHP and MySQL when handling an e-
commerce order process that involves multiple related database inserts (e.g., order,
order_items, payments). -->

Why Use Transactions


Atomicity: Transactions ensure that a series of operations either complete entirely or not at all. This is vital in an e-commerce context where an order may consist of multiple related actions. If one part of the process fails (e.g., payment fails after the order is created), we want to avoid leaving the database in an inconsistent state.

Consistency: Transactions help maintain data integrity by ensuring that the database transitions from one valid state to another. For instance, if an order is created but the corresponding payment is not processed, this would violate consistency.

Isolation: Transactions provide isolation from other operations, meaning that intermediate states are not visible to other transactions. This prevents issues such as dirty reads, where one transaction reads uncommitted changes made by another.

Durability: Once a transaction is committed, the changes are permanent, even in the event of a system failure. This guarantees that completed orders and payments are reliably stored.

<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=test', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount) VALUES (:user_id, :total_amount)");
    $stmt->execute(['user_id' => $userId, 'total_amount' => $totalAmount]);
    $orderId = $pdo->lastInsertId();

    foreach ($orderItems as $item) {
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity) VALUES (:order_id, :product_id, :quantity)");
        $stmt->execute(['order_id' => $orderId, 'product_id' => $item['product_id'], 'quantity' => $item['quantity']]);
    }

    $stmt = $pdo->prepare("INSERT INTO payments (order_id, amount, payment_method) VALUES (:order_id, :amount, :payment_method)");
    $stmt->execute(['order_id' => $orderId, 'amount' => $totalAmount, 'payment_method' => $paymentMethod]);

    $pdo->commit();
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Failed to process order: " . $e->getMessage();
}

?>
