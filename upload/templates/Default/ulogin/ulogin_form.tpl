<div class="ulogin_form">
    [uloginid]
        <div data-uloginid="{uloginid}" data-ulogin="redirect_uri={redirect_uri};callback={callback}"></div>
    [/uloginid]
    [not-uloginid]
        <div data-ulogin="display=small;fields=first_name,last_name,email;optional=phone,city,country,nickname,sex,photo_big,bdate,photo;providers=vkontakte,odnoklassniki,mailru,facebook;hidden=other;redirect_uri={redirect_uri};callback={callback}"></div>
    [/not-uloginid]
</div>