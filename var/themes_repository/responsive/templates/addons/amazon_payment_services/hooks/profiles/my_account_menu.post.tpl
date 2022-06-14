{if !empty($smarty.session.auth.user_id)}
<li class="ty-account-info__item ty-dropdown-box__item"><a class="ty-account-info__a" href="{"profiles.payment_methods"|fn_url}" rel="nofollow">{__("payment_methods")}</a></li>
{/if}