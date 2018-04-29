<?php

require_once 'Includes/connection.php';
include 'header.php';


$msg="";
 
 if(!empty($_GET['msg']) )
{
$msg = $_GET['msg'];
}
$orderId = null;
if($_SERVER['REQUEST_METHOD'] =='POST'){
    if(isset( $_POST['checkout'] )) {
     $stmt = $conn->prepare("SELECT p.ProductID,c.Quantity,(SELECT SUM(pp.Price * cc.Quantity) FROM Carts AS cc LEFT JOIN Products AS pp on cc.product_id=pp.ProductID WHERE cc.member_id=? GROUP BY member_id) as total FROM Carts AS c LEFT JOIN Products AS p on c.product_id=p.ProductID WHERE c.member_id=?");
     $stmt->bind_param("ii", $_SESSION['memberID'], $_SESSION['memberID']);
     $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
    
        if(is_null($orderId)) 
        {
            $stmt = $conn->prepare("INSERT INTO Orders(customer_id,address_id,Date_Time,TotalCost,Status) VALUES(?,?,NOW(),?,'Placed')");
            $stmt->bind_param("iii",$_SESSION['memberID'], $_POST['address'],$row['total']);
            if($stmt->execute()){
            $msg.= "Order created sucessfully!";
            $orderId = $stmt->insert_id;
            }
            else{
                $msg.= "Failed!!";
            }
        }
        
        
        //  decrement Product quantity
        $stmt = $conn->prepare("SELECT Stock FROM Products WHERE ProductID = ?");
        $stmt->bind_param("i", $row['ProductID']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row2 = $result->fetch_assoc();
        if($row2['Stock'] >= $row['Quantity']){
        
            /// insert into Orderproducts
            
            $stmt = $conn->prepare("INSERT INTO OrderProducts(order_id,product_id,Quantity) VALUES(?,?,?)");
            $stmt->bind_param("iii", $orderId, $row['ProductID'],$row['Quantity'] );
            if($stmt->execute()){
                $newQuantity = $row2['Stock'] - $row['Quantity'];
                $stmt = $conn->prepare("UPDATE Products SET Stock = ? WHERE ProductID = ?");
                $stmt->bind_param("ii", $newQuantity, $row['ProductID'] );
                $stmt->execute();
            }
        
        }else{
           
           $msg.= "A Product with ".$row['ProductID']."is out of stock!"; 
        }
        
    }
            $stmt = $conn->prepare("DELETE FROM Carts WHERE member_id=?");
            $stmt->bind_param("i", $_SESSION['memberID'] );
           $stmt->execute();
    
    
    
    }
}


