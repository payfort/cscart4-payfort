<?php
if ($mode == 'place_order') {
    if (!empty($_REQUEST['payment_id'])) {
        fn_payfort_fort_delete_old_order($_REQUEST['payment_id']);
    }
}
?>