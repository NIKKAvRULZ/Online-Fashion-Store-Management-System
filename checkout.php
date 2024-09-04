<?php

include 'components/connect.php';

if(isset($_COOKIE['user_id'])){
   $user_id = $_COOKIE['user_id'];
}else{
   setcookie('user_id', create_unique_id(), time() + 60*60*24*30);
}

if(isset($_POST['place_order'])){

   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $number = $_POST['number'];
   $number = filter_var($number, FILTER_SANITIZE_STRING);
   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_STRING);
   $address = $_POST['flat'].', '.$_POST['street'].', '.$_POST['city'].', '.$_POST['country'].' - '.$_POST['pin_code'];
   $address = filter_var($address, FILTER_SANITIZE_STRING);
   $address_type = $_POST['address_type'];
   $address_type = filter_var($address_type, FILTER_SANITIZE_STRING);
   $method = $_POST['method'];
   $method = filter_var($method, FILTER_SANITIZE_STRING);

   $verify_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
   $verify_cart->execute([$user_id]);
   
   if(isset($_GET['get_id'])){

      $get_product = $conn->prepare("SELECT * FROM `products` WHERE id = ? LIMIT 1");
      $get_product->execute([$_GET['get_id']]);
      if($get_product->rowCount() > 0){
         while($fetch_p = $get_product->fetch(PDO::FETCH_ASSOC)){
            $insert_order = $conn->prepare("INSERT INTO `orders`(id, user_id, name, number, email, address, address_type, method, product_id, price, qty) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
            $insert_order->execute([create_unique_id(), $user_id, $name, $number, $email, $address, $address_type, $method, $fetch_p['id'], $fetch_p['price'], 1]);
            header('location:orders.php');
         }
      }else{
         $warning_msg[] = 'Something went wrong!';
      }

   }elseif($verify_cart->rowCount() > 0){

      while($f_cart = $verify_cart->fetch(PDO::FETCH_ASSOC)){

         $insert_order = $conn->prepare("INSERT INTO `orders`(id, user_id, name, number, email, address, address_type, method, product_id, price, qty) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
         $insert_order->execute([create_unique_id(), $user_id, $name, $number, $email, $address, $address_type, $method, $f_cart['product_id'], $f_cart['price'], $f_cart['qty']]);

      }

      if($insert_order){
         $delete_cart_id = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
         $delete_cart_id->execute([$user_id]);
         header('location:orders.php');
      }

   }else{
      $warning_msg[] = 'Your cart is empty!';
   }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Checkout</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">

   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'components/header.php'; ?>

<section class="checkout">

   <h1 class="heading">checkout summary</h1>

   <div class="row">

      <form action="" method="POST">
         <h3>billing details</h3>
         <div class="flex">
            <div class="box">
               <p>your name <span>*</span></p>
               <input type="text" name="name" required maxlength="50" placeholder="enter your name" class="input">
               <p>your number <span>*</span></p>
               <input type="number" name="number" required maxlength="10" placeholder="enter your number" class="input" min="0" max="9999999999">
               <p>your email <span>*</span></p>
               <input type="email" name="email" required maxlength="50" placeholder="enter your email" class="input">
               <p>payment method <span>*</span></p>
               <select name="method" class="input" required>
                  <option value="cash on delivery">cash on delivery</option>
                  <option value="credit or debit card">credit or debit card</option>
                  <option value="net banking">net banking</option>
                  <option value="UPI or wallets">UPI or RuPay</option>
               </select>
               <p>address type <span>*</span></p>
               <select name="address_type" class="input" required> 
                  <option value="home">home</option>
                  <option value="office">office</option>
               </select>
            </div>
            <div class="box">
               <p>address line 01 <span>*</span></p>
               <input type="text" name="flat" required maxlength="50" placeholder="e.g. flat & building number" class="input">
               <p>address line 02 <span>*</span></p>
               <input type="text" name="street" required maxlength="50" placeholder="e.g. street name & locality" class="input">
               <p>city name <span>*</span></p>
               <input type="text" name="city" required maxlength="50" placeholder="enter your city name" class="input">
               <p>country name <span>*</span></p>
               <input type="text" name="country" required maxlength="50" placeholder="enter your country name" class="input">
               <p>pin code <span>*</span></p>
               <input type="number" name="pin_code" required maxlength="6" placeholder="e.g. 123456" class="input" min="0" max="999999">
            </div>
         </div>
         <input type="submit" value="place order" name="place_order" class="btn">
      </form>

      <div class="summary">
         <h3 class="title">cart items</h3>
         <?php
            $grand_total = 0;
            if(isset($_GET['get_id'])){
               $select_get = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
               $select_get->execute([$_GET['get_id']]);
               while($fetch_get = $select_get->fetch(PDO::FETCH_ASSOC)){
         ?>
         <div class="flex">
            <img src="uploaded_files/<?= $fetch_get['image']; ?>" class="image" alt="">
            <div>
               <h3 class="name"><?= $fetch_get['name']; ?></h3>
               <p class="price"><i class="fas fa-indian-rupee-sign"></i> <?= $fetch_get['price']; ?> x 1</p>
            </div>
         </div>
         <?php
               }
            }else{
               $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
               $select_cart->execute([$user_id]);
               if($select_cart->rowCount() > 0){
                  while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
                     $select_products = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
                     $select_products->execute([$fetch_cart['product_id']]);
                     $fetch_product = $select_products->fetch(PDO::FETCH_ASSOC);
                     $sub_total = ($fetch_cart['qty'] * $fetch_product['price']);

                     $grand_total += $sub_total;
            
         ?>
         <div class="flex">
            <img src="uploaded_files/<?= $fetch_product['image']; ?>" class="image" alt="">
            <div>
               <h3 class="name"><?= $fetch_product['name']; ?></h3>
               <p class="price"><i class="fas fa-indian-rupee-sign"></i> <?= $fetch_product['price']; ?> x <?= $fetch_cart['qty']; ?></p>
            </div>
         </div>
         <?php
                  }
               }else{
                  echo '<p class="empty">your cart is empty</p>';
               }
            }
         ?>
         <div class="grand-total"><span>grand total :</span><p><i class="fas fa-indian-rupee-sign"></i> <?= $grand_total; ?></p></div>
      </div>

   </div>

</section>





<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

<script src="js/script.js"></script>

<?php include 'components/alert.php'; ?>

</body>
</html>