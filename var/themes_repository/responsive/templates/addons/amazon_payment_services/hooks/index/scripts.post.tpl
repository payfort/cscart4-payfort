{if $runtime.controller eq 'checkout'}
	{script src="js/lib/inputmask/jquery.inputmask.min.js"}
	{script src="js/addons/amazon_payment_services/credit-card-validator.js"}
	{script src="js/addons/amazon_payment_services/visa-checkout.js"}
	{script src="js/addons/amazon_payment_services/slick.min.js"}
	{if $runtime.mode eq 'checkout'}
		{script src="js/addons/amazon_payment_services/apple-pay/checkout.js"}
	{/if}
	{if $runtime.mode eq 'cart'}
		{script src="js/addons/amazon_payment_services/apple-pay/cart.js"}
	{/if}
{/if}
{if $runtime.controller eq 'products'}
	{script src="js/addons/amazon_payment_services/apple-pay/product.js"}
{/if}
{if $runtime.controller eq 'checkout'}
	{script src="js/addons/amazon_payment_services/core.js"}
{/if}