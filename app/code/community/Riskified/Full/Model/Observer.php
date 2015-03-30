<?php
use Riskified\Common\Riskified;
use Riskified\Common\Env;
use Riskified\Common\Validations;
use Riskified\Common\Signature;
use Riskified\OrderWebhook\Model;
use Riskified\OrderWebhook\Transport;

class Riskified_Full_Model_Observer {

    public function saveOrderBefore($evt) {
        Mage::helper('full/log')->log("saveOrderBefore");

        $payment = $evt->getPayment();
        $cc_bin = substr($payment->getCcNumber(),0,6);

        if ($cc_bin) {
            $payment->setAdditionalInformation('riskified_cc_bin', $cc_bin);
        }
    }

    public function saveRiskifiedConfig($evt) {
        Mage::helper('full/log')->log("saveRiskifiedConfig");
        $helper = Mage::helper('full');
        $riskifiedShopDomain =  $helper->getShopDomain();
        $StatusControlActive = $helper->getConfigStatusControlActive();
        $approvedState = $helper->getApprovedState();
        $declinedState = $helper->getDeclinedState();
        $autoInvoiceCaptureCase = $helper->getConfigAutoInvoiceCaptureCase();
        $enableAutoInvoice = $helper->getConfigEnableAutoInvoice();
        $authToken = $helper->getAuthToken();
        $all_active_methods = Mage::getModel('payment/config')->getActiveMethods();
        $allkeys ='';
        foreach ($all_active_methods as $key => $value)
        {
            $allkeys .= $key.",";
        }
        $extensionVersion = Mage::helper('full')->getExtensionVersion();
        $shopHostUrl =  Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

        #Riskified::init($riskifiedShopDomain, $authToken, $env, Validations::IGNORE_MISSING);
        $settings = new Model\MerchantSettings(array(
            'settings' => array(
                'gws' => $allkeys,
                'host_url' => $shopHostUrl,
                'extension_version' => $extensionVersion,
                'status_control_active' => $StatusControlActive,
                'approved_state' => $approvedState,
                'declined_state' => $declinedState,
                'auto_invoice_capture_case' => $autoInvoiceCaptureCase,
                'enable_auto_invoice' => $enableAutoInvoice)
        ));
        if($authToken && $riskifiedShopDomain) {
            Mage::helper('full/order')->updateMerchantSettings($settings);
        }
    }

    public function salesOrderPaymentPlaceEnd($evt) {
	    Mage::helper('full/log')->log("salesOrderPaymentPlaceEnd");

        //$order = $evt->getPayment()->getOrder();

        //try {
            // Mage::helper('full/order')->postOrder($order, Riskified_Full_Helper_Order::ACTION_CREATE);
        //} catch (Exception $e) {
            // There is no need to do anything here.  The exception has already been handled and a retry scheduled.
            // We catch this exception so that the order is still saved in Magento.
        //}
    }

    public function salesOrderPaymentVoid($evt) {
        Mage::helper('full/log')->log("salesOrderPaymentVoid");
        //$order = $evt->getPayment()->getOrder();
        //Mage::helper('full/order')->postOrder($order,'cancel');
    }

    public function salesOrderPaymentRefund($evt) {
        Mage::helper('full/log')->log("salesOrderPaymentRefund");
        //$order = $evt->getPayment()->getOrder();
        //Mage::helper('full/order')->postOrder($order,'cancel');
    }

    public function salesOrderPaymentCancel($evt) {
        Mage::helper('full/log')->log("salesOrderPaymentCancel");
        //$order = $evt->getPayment()->getOrder();
        //Mage::helper('full/order')->postOrder($order,'cancel');
    }

    public function salesOrderPlaceBefore($evt) {
        Mage::helper('full/log')->log("salesOrderPlaceBefore");
    }

    public function salesOrderPlaceAfter($evt) {
        Mage::helper('full/log')->log("salesOrderPlaceAfter");
    }

    public function salesOrderSaveBefore($evt) {
        Mage::helper('full/log')->log("salesOrderSaveBefore");
    }

    public function salesOrderSaveAfter($evt) {
        Mage::helper('full/log')->log("salesOrderSaveAfter");

        $order = $evt->getOrder();
        if(!$order) {
            return;
        }

        $newState = $order->getState();

        if ($order->dataHasChangedFor('state')) {
            Mage::helper('full/log')->log("Order: " . $order->getId() . " state changed from: " . $order->getOrigData('state') . " to: " . $newState);

            // if we posted we should not re post
            if($order->riskifiedInSave) {
                Mage::helper('full/log')->log("Order : " . $order->getId() . " is already riskifiedInSave");
                return;
            }
            // Flag order to indicate that we are posting
            $order->riskifiedInSave = true;

            try {
                Mage::helper('full/order')->postOrder($order, Riskified_Full_Helper_Order::ACTION_UPDATE);
            } catch (Exception $e) {
                // There is no need to do anything here.  The exception has already been handled and a retry scheduled.
                // We catch this exception so that the order is still saved in Magento.
            }
        } else {
            Mage::helper('full/log')->log("Order: '" . $order->getId() . "' state didn't change on save - not posting again: " . $newState);
        }
    }

    public function salesOrderCancel($evt) {
        Mage::helper('full/log')->log("salesOrderCancel");

        $order = $evt->getOrder();
        
        // TODO not sure if this is still required - saveAfter should be enough

        try {
            Mage::helper('full/order')->postOrder($order, Riskified_Full_Helper_Order::ACTION_CANCEL);
        } catch (Exception $e) {
            // There is no need to do anything here.  The exception has already been handled and a retry scheduled.
            // We catch this exception so that the order is still saved in Magento.
        }
    }

    public function postOrderIds($order_ids) {
        foreach ($order_ids as $order_id) {
            $order = Mage::getModel('sales/order')->load($order_id);

            try {
                Mage::helper('full/order')->postOrder($order, Riskified_Full_Helper_Order::ACTION_SUBMIT);
            } catch (Exception $e) {
                // There is no need to do anything here.  The exception has already been handled and a retry scheduled.
                // We catch this exception so that the order is still saved in Magento.
            }
        }
    }

    public function addMassAction($observer) {
        $block = $observer->getEvent()->getBlock();
        if(get_class($block) =='Mage_Adminhtml_Block_Widget_Grid_Massaction'
            && $block->getRequest()->getControllerName() == 'sales_order')
        {
            $block->addItem('full', array(
                'label' => 'Submit to Riskified',
                'url' => Mage::app()->getStore()->getUrl('full/adminhtml_full/riskimass'),
            ));
        }
    }

    public function blockHtmlBefore($observer) {
        $block = $observer->getEvent()->getBlock();
        if ($block->getType() == 'adminhtml/sales_order_view') {
            $message = Mage::helper('sales')->__('Are you sure you want to submit this order to Riskified?');
            $url = $block->getUrl('full/adminhtml_full/riski');
            $block->addButton('riski_submit', array(
                'label'     => Mage::helper('sales')->__('Submit to Riskified'),
                'onclick'   => "deleteConfirm('$message', '$url')",
            ));
        }
    }

	/**
	 * Update the order state and status when it's been updated
	 *
	 * @param Varien_Event_Observer $observer
	 */
    public function updateOrderState(Varien_Event_Observer $observer)
	{
		$riskifiedOrderStatusHelper = Mage::helper('full/order_status');
        $riskifiedInvoiceHelper = Mage::helper('full/order_invoice');
		$order = $observer->getOrder();
		$riskifiedStatus = (string) $observer->getStatus();
        $riskifiedOldStatus = (string) $observer->getOldStatus();
		$description = (string) $observer->getDescription();
		$newState = $newStatus = null;
		$currentState = $order->getState();
		$currentStatus = $order->getStatus();

		Mage::helper('full/log')->log("Checking if should update order '" . $order->getId() . "' from state: '$currentState' and status: '$currentStatus'");
        Mage::helper('full/log')->log("Data received from riskified: status: " . $riskifiedStatus . ", old_status: "  . $riskifiedOldStatus . ", description: " . $description);

		switch ($riskifiedStatus) {
			case 'approved':
				if ($currentState == Mage_Sales_Model_Order::STATE_HOLDED
				    && ($currentStatus == $riskifiedOrderStatusHelper->getOnHoldStatusCode()
                        || $currentStatus == $riskifiedOrderStatusHelper->getTransportErrorStatusCode())) {
                    $newState = $riskifiedOrderStatusHelper->getSelectedApprovedState();
                    $newStatus = $riskifiedOrderStatusHelper->getSelectedApprovedStatus();
				}

				break;
			case 'declined':
                if ($currentState == Mage_Sales_Model_Order::STATE_HOLDED
                    && ($currentStatus == $riskifiedOrderStatusHelper->getOnHoldStatusCode()
                        || $currentStatus == $riskifiedOrderStatusHelper->getTransportErrorStatusCode())) {
					$newState = $riskifiedOrderStatusHelper->getSelectedDeclinedState();
					$newStatus = $riskifiedOrderStatusHelper->getSelectedDeclinedStatus();
				}

				break;
			case 'submitted':
				if ($currentState == Mage_Sales_Model_Order::STATE_PROCESSING
                    || ($currentState == Mage_Sales_Model_Order::STATE_HOLDED
                        && $currentStatus == $riskifiedOrderStatusHelper->getTransportErrorStatusCode())) {
					$newState = Mage_Sales_Model_Order::STATE_HOLDED;
					$newStatus = $riskifiedOrderStatusHelper->getOnHoldStatusCode();
				}

				break;
            case 'error':
                if ($currentState == Mage_Sales_Model_Order::STATE_PROCESSING
                    && $riskifiedInvoiceHelper->isAutoInvoiceEnabled()) {
                    $newState = Mage_Sales_Model_Order::STATE_HOLDED;
                    $newStatus = $riskifiedOrderStatusHelper->getTransportErrorStatusCode();
                }
		}

        $changed = false;

        // if newState exists and new state/status are different from current and config is set to status-sync
		if ($newState
            && ($newState != $currentState || $newStatus != $currentStatus)
			 && Mage::helper('full')->getConfigStatusControlActive()) {
            if ($newState == Mage_Sales_Model_Order::STATE_CANCELED) {
                Mage::helper('full/log')->log("Order '" . $order->getId() . "' should be canceled - calling cancel method");
                $order->cancel();
            }
            $order->setState($newState, $newStatus, $description);
            Mage::helper('full/log')->log("Updated order '" . $order->getId()   . "' to: state:  '$newState', status: '$newStatus', description: '$description'");
            $changed=true;
		} elseif ($description && $riskifiedStatus != $riskifiedOldStatus) {
            Mage::helper('full/log')->log("Updated order " . $order->getId() . " history comment to: "  . $description);
            $order->addStatusHistoryComment($description);
            $changed=true;
        } else {
            Mage::helper('full/log')->log("No update to state,status,comments is required for " . $order->getId());
        }


        if ($changed) {
			try {
				$order->save();
			} catch (Exception $e) {
				Mage::helper('full/log')->log("Error saving order: " . $e->getMessage());
				return;
			}
		}
	}

	/**
	 * Create an invoice when the order is approved
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function autoInvoice(Varien_Event_Observer $observer)
	{
		$riskifiedInvoiceHelper = Mage::helper('full/order_invoice');

		if (!$riskifiedInvoiceHelper->isAutoInvoiceEnabled()) {
			return;
		}

		$order = $observer->getOrder();

		// Sanity check
		if (!$order || !$order->getId()) {
			return;
		}

		Mage::helper('full/log')->log("Auto-invoicing  order " . $order->getId());

		if (!$order->canInvoice()) {
			Mage::helper('full/log')->log("Order cannot be invoiced");
			return;
		}

		$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

		if (!$invoice->getTotalQty()) {
			Mage::helper('full/log')->log("Cannot create an invoice without products");
			return;
		}

		try {
			$invoice
				->setRequestedCaptureCase($riskifiedInvoiceHelper->getCaptureCase())
				->addComment(
					'Invoice automatically created by Riskified when order was approved',
					false,
					false
				)
				->register();
		} catch (Exception $e) {
			Mage::helper('full/log')->log("Error creating invoice: " . $e->getMessage());
			return;
		}

		try {
			Mage::getModel('core/resource_transaction')
			    ->addObject($invoice)
			    ->addObject($order)
			    ->save();
		} catch (Exception $e) {
			Mage::helper('full/log')->log("Error creating transaction: " . $e->getMessage());
			return;
		}

		Mage::helper('full/log')->log("Transaction saved");
	}

    /**
     * Clear all submission retries for an order that have the same action.
     * This event observer is only called after a successful submission.
     *
     * @param Varien_Event_Observer $observer
     */
    public function clearRetriesForOrder(Varien_Event_Observer $observer)
    {
        $order = $observer->getOrder();

        // Sanity check
        if (!$order || !$order->getId()) {
            return;
        }

        $retries = Mage::getModel('full/retry')->getCollection()
            ->addfieldtofilter('order_id', $order->getId())
            ->addFieldToFilter('action', $observer->getAction());

        foreach($retries as $retry) {
            $retry->delete();
        }
    }

    /**
     * Process the response from a successful post to Riskified.
     *
     * @param Varien_Event_Observer $observer
     */
    public function processSuccessfulPost(Varien_Event_Observer $observer)
    {
        /* @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();

        /* @var stdClass $response */
        $response = $observer->getResponse();

        if (isset($response->order)) {
            $orderId = $response->order->id;
            $status = $response->order->status;
            $oldStatus = $response->order->old_status;
            $description = $response->order->description;

            if (!$description) {
                $description = "Riskified Status: $status";
            }

            if ($orderId && $status) {
                Mage::helper('full/order')->updateOrder($order, $status, $oldStatus, $description);
            }

            $name = $order->getIncrementId();

            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__("Order #$name was successfully updated at Riskified"));
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__("Malformed response from Riskified"));
        }
    }
}