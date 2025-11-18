<?php

class OrderHistory extends OrderHistoryCore
{
    public function addWithemail($autodate = true, $template_vars = false, Context $context = null)
    {
        $allegro = _PS_MODULE_DIR_ . 'x13allegro/x13allegro.php';

        if (file_exists($allegro))
        {
            require_once ($allegro);

            if (Module::isEnabled('x13allegro')
                && XAllegroForm::orderExists($this->id_order)
                && !(bool)XAllegroConfiguration::get('ORDER_SEND_CUSTOMER_MAIL')
            ) {
                return $this->add($autodate);
            }
        }

        return parent::addWithemail($autodate, $template_vars, $context);
    }

    public function sendEmail($order, $template_vars = false)
    {
        $allegro = _PS_MODULE_DIR_ . 'x13allegro/x13allegro.php';

        if (file_exists($allegro)) {
            require_once ($allegro);

            if (Module::isEnabled('x13allegro')
                && XAllegroForm::orderExists($this->id_order)
                && !(bool)XAllegroConfiguration::get('ORDER_SEND_CUSTOMER_MAIL')
            ) {
                return true;
            }
        }

        return parent::sendEmail($order, $template_vars);
    }
}
