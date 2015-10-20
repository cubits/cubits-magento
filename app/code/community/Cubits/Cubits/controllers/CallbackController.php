<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

class Cubits_Cubits_CallbackController extends Mage_Core_Controller_Front_Action
{

    public function callbackAction() {

      require_once(Mage::getModuleDir('cubits-php', 'Cubits_Cubits') . "/cubits-php/lib/Cubits.php");

      $secret = $_REQUEST['secret'];

      $postBody = json_decode(file_get_contents('php://input'));

      $correctSecret = Mage::getStoreConfig('payment/Cubits/callback_secret');

      // To verify this callback is legitimate, we will:
      //   a) check with Cubits the submitted order information is correct.
      $apiKey = Mage::getStoreConfig('payment/Cubits/api_key');
      $apiSecret = Mage::getStoreConfig('payment/Cubits/api_secret');

      Cubits::configure("https://pay.cubits.com/api/v1/",true);
      $cubits = Cubits::withApiKey($apiKey, $apiSecret);

      $cbOrderId = $postBody->id;

      $orderInfo = $cubits->getInvoice($cbOrderId);
         
      if(!$orderInfo) {
        Mage::log("Cubits: incorrect callback with incorrect Cubits order ID $cbOrderId.");
        header("HTTP/1.1 500 Internal Server Error");
        return;
      }

      //   b) using the verified order information, check which order the transaction was for using the custom param.
      $orderId = $orderInfo->reference;

      $order = Mage::getModel('sales/order')->load($orderId);
      if(!$order) {
        Mage::log("Cubits: incorrect callback with incorrect order ID $orderId.");
        header("HTTP/1.1 500 Internal Server Error");
        return;
      }

      //   c) check the secret URL parameter.
      if($secret !== $correctSecret) {
        Mage::log("Cubits: incorrect callback with incorrect secret parameter $secret.");
        header("HTTP/1.1 500 Internal Server Error");
        return;
      }

      // The callback is legitimate. Update the order's status in the database.
      $payment = $order->getPayment();
      $payment->setTransactionId($cbOrderId)
        ->setPreparedMessage("Paid with Cubits order $cbOrderId.")
        ->setShouldCloseParentTransaction(true)
        ->setIsTransactionClosed(0);

      if(("completed" == $orderInfo->status)||("overpaid" == $orderInfo->status)) {
        $payment->registerCaptureNotification($orderInfo->merchant_amount);
      } else {
        $cancelReason = $postBody->cancellation_reason;
        $order->registerCancellation("Cubits order $cbOrderId cancelled: $cancelReason");
      }

      Mage::dispatchEvent('cubits_callback_received', array('status' => $orderInfo->status, 'order_id' => $orderId));
      $order->save();
    }

}
