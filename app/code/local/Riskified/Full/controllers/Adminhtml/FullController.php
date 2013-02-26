<?php

class Riskified_Full_Adminhtml_FullController extends Mage_Adminhtml_Controller_action
{

	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('full/items')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));
		
		return $this;
	}   
	
	
	/*****mycode*****
	public function riskiAction(){
		$order_id = $this->getRequest()->getParam('order_id');
		echo"<pre>";
		$order_model = Mage::getModel('sales/order')->load($order_id);
		$billing_address = $order_model->getBillingAddress();
		$shipping_address = $order_model->getShippingAddress();
		$data = array();
		$data['id'] 			= $order_model->getId();
		$data['shipping_line'] 	= $order_model->getShippingDescription();
		$data['email']			= $order_model->getCustomerEmail();
		$data['total_spent']	= $order_model->getGrandTotal();
		$data['created_at']		= $order_model->getCreatedAt();
		//$data['gateway']		=;
		$data['browser_ip']		= $order_model->getRemoteIp();
		
		$data['billing_address']['first_name'] 	= $billing_address->getFirstname();
		$data['billing_address']['last_name']	= $billing_address->getLastname();
		$data['billing_address']['address1'] 	= $billing_address->getStreet();
		$data['billing_address']['address2'] 	= '';
		$data['billing_address']['city'] 		= $billing_address->getCity();
		$data['billing_address']['company'] 	= $billing_address->getCompany();
		$data['billing_address']['country'] 	= Mage::getModel('directory/country')->load($billing_address->getCountryId())->getName();
		$data['billing_address']['phone'] 		= $billing_address->getTelephone();
		$data['billing_address']['province'] 	= $billing_address->getRegion();
		$data['billing_address']['zip'] 		= $billing_address->getPostcode();
		
		$data['shipping_address']['first_name'] = $shipping_address->getFirstname();
		$data['shipping_address']['last_name'] 	= $shipping_address->getLastname();
		$data['shipping_address']['address1'] 	= $shipping_address->getgetStreet();
		$data['shipping_address']['address2'] 	= '';
		$data['shipping_address']['city'] 		= $shipping_address->getCity();
		$data['shipping_address']['company'] 	= $shipping_address->getCompany();
		$data['shipping_address']['country'] 	= Mage::getModel('directory/country')->load($shipping_address->getCountryId())->getName();
		$data['shipping_address']['phone'] 		= $shipping_address->getTelephone();
		$data['shipping_address']['province'] 	= $shipping_address->getRegion();
		$data['shipping_address']['zip'] 		= $shipping_address->getPostcode();
		
		
		
		
		
		
		print_r($order_model->getData());
		echo"</pre>";
		die;
	}
	
	*/
	
	
 
	public function indexAction() {
		$this->_initAction()
			->renderLayout();
	}

	public function editAction() {
		$id     = $this->getRequest()->getParam('id');
		$model  = Mage::getModel('full/full')->load($id);

		if ($model->getId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('full_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu('full/items');

			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('full/adminhtml_full_edit'))
				->_addLeft($this->getLayout()->createBlock('full/adminhtml_full_edit_tabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('full')->__('Item does not exist'));
			$this->_redirect('*/*/');
		}
	}
 
	public function newAction() {
		$this->_forward('edit');
	}
 
	public function saveAction() {
		if ($data = $this->getRequest()->getPost()) {
			
			if(isset($_FILES['filename']['name']) && $_FILES['filename']['name'] != '') {
				try {	
					/* Starting upload */	
					$uploader = new Varien_File_Uploader('filename');
					
					// Any extention would work
	           		$uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
					$uploader->setAllowRenameFiles(false);
					
					// Set the file upload mode 
					// false -> get the file directly in the specified folder
					// true -> get the file in the product like folders 
					//	(file.jpg will go in something like /media/f/i/file.jpg)
					$uploader->setFilesDispersion(false);
							
					// We set media as the upload dir
					$path = Mage::getBaseDir('media') . DS ;
					$uploader->save($path, $_FILES['filename']['name'] );
					
				} catch (Exception $e) {
		      
		        }
	        
		        //this way the name is saved in DB
	  			$data['filename'] = $_FILES['filename']['name'];
			}
	  			
	  			
			$model = Mage::getModel('full/full');		
			$model->setData($data)
				->setId($this->getRequest()->getParam('id'));
			
			try {
				if ($model->getCreatedTime == NULL || $model->getUpdateTime() == NULL) {
					$model->setCreatedTime(now())
						->setUpdateTime(now());
				} else {
					$model->setUpdateTime(now());
				}	
				
				$model->save();
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('full')->__('Item was successfully saved'));
				Mage::getSingleton('adminhtml/session')->setFormData(false);

				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('id' => $model->getId()));
					return;
				}
				$this->_redirect('*/*/');
				return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('full')->__('Unable to find item to save'));
        $this->_redirect('*/*/');
	}
 
	public function deleteAction() {
		if( $this->getRequest()->getParam('id') > 0 ) {
			try {
				$model = Mage::getModel('full/full');
				 
				$model->setId($this->getRequest()->getParam('id'))
					->delete();
					 
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item was successfully deleted'));
				$this->_redirect('*/*/');
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
			}
		}
		$this->_redirect('*/*/');
	}

    public function massDeleteAction() {
        $fullIds = $this->getRequest()->getParam('full');
        if(!is_array($fullIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($fullIds as $fullId) {
                    $full = Mage::getModel('full/full')->load($fullId);
                    $full->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count($fullIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
	
    public function massStatusAction()
    {
        $fullIds = $this->getRequest()->getParam('full');
        if(!is_array($fullIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select item(s)'));
        } else {
            try {
                foreach ($fullIds as $fullId) {
                    $full = Mage::getSingleton('full/full')
                        ->load($fullId)
                        ->setStatus($this->getRequest()->getParam('status'))
                        ->setIsMassupdate(true)
                        ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) were successfully updated', count($fullIds))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
  
    public function exportCsvAction()
    {
        $fileName   = 'full.csv';
        $content    = $this->getLayout()->createBlock('full/adminhtml_full_grid')
            ->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName   = 'full.xml';
        $content    = $this->getLayout()->createBlock('full/adminhtml_full_grid')
            ->getXml();

        $this->_sendUploadResponse($fileName, $content);
    }

    protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
    {
        $response = $this->getResponse();
        $response->setHeader('HTTP/1.1 200 OK','');
        $response->setHeader('Pragma', 'public', true);
        $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        $response->setHeader('Content-Disposition', 'attachment; filename='.$fileName);
        $response->setHeader('Last-Modified', date('r'));
        $response->setHeader('Accept-Ranges', 'bytes');
        $response->setHeader('Content-Length', strlen($content));
        $response->setHeader('Content-type', $contentType);
        $response->setBody($content);
        $response->sendResponse();
        die;
    }
    
    
}