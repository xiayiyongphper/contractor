<?php

function myErrorHandler($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting, so let it fall
        // through to the standard PHP error handler
        return false;
    }

    switch ($errno) {
        case E_USER_ERROR:
            echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
            echo "  Fatal error on line $errline in file $errfile";
            echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
            echo "Aborting...<br />\n";
            exit(1);
            break;

        case E_USER_WARNING:
            echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
            break;

        case E_USER_NOTICE:
            echo "<b>My NOTICE</b> [$errno] $errstr<br />\n";
            break;

        default:
            echo "Unknown error type: [$errno] $errstr<br />\n";
            print_r(debug_backtrace());
            break;
    }

    /* Don't execute PHP internal error handler */
    return true;
}


set_error_handler("myErrorHandler");

try{
    $permission = unserialize("[\"home\",\"home\\/home-contractor-mark-price\",\"home\\/home-contractor-order-tracking\",\"home\\/home-contractor-register-audit\",\"home\\/home-contractor-statics\",\"home\\/home-contractor-store\",\"home\\/home-contractor-store-list\",\"home\\/home-contractor-visit-record\",\"order\",\"order\\/contractor-order-detail\",\"order\\/order-status-collection\",\"order\\/order-tracking-collection\",\"store\",\"store\\/contractor-edit-store\",\"store\\/contractor-mark-price-list\",\"store\\/contractor-mark-price-product-detail\",\"store\\/contractor-merchant-collection\",\"store\\/contractor-review-store-list\",\"store\\/contractor-store-detail\",\"store\\/contractor-store-intention-create\",\"store\\/contractor-store-search\",\"store\\/contractor-store-visit-brief-new\",\"store\\/contractor-store-visit-new\",\"store\\/contractor-visit-store-list\"]");
}catch (\Exception $e){
    print_r($e->getMessage());
}catch (\Error $e){
    print_r($e->getMessage());
}
