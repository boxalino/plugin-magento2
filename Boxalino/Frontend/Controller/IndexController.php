<?php

class Boxalino_CemSearch_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function saveAction()
    {
        $name = '' . $this->getRequest()->getPost('name');
        $surname = '' . $this->getRequest()->getPost('surname');
        $phone = '' . $this->getRequest()->getPost('phone');
        if (isset($name) && ($name != '') && isset($surname) && ($surname != '') && isset($phone) && ($phone != '')) {
            $contact = Mage::getModel('test/test');
            $contact->setData('name', $name);
            $contact->setData('surname', $surname);
            $contact->setData('phone', $phone);
            $contact->save();
        }
        $this->_redirect('test/index/index');
    }
}

?>