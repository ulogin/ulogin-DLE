<br/>
<br/>
<div class="ulogin_profile">
    <div class="ulogin_label">{add_account}</div>
        {include file="engine/modules/ulogin/ulogin_tpl_form.php?uloginid={uloginid}&ulogin_online=true"}
    <div class="ulogin_note"><small>{add_account_explain}</small></div>

    <div class="delete_accounts" style="display: {display}">
        <div class="ulogin_label">{delete_account}</div>
        <div class="ulogin_accounts can_delete">
            {networks}
        </div><div style="clear:both"></div>
        <div class="ulogin_note"><small>{delete_account_explain}</small></div>
    </div>
</div>