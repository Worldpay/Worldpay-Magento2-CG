<?php
namespace Sapient\Worldpay\Plugin;

class CsrfValidatorDisable
{
    /**
     * Around validate
     *
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ActionInterface $action
     */
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        $request,
        $action
    ) {
        if ($request->getModuleName() == 'worldpay') {
            return; // Disable CSRF check
        }
        $proceed($request, $action); // Proceed Magento 2 core functionalities
    }
}
