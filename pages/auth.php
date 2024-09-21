<?php include_once "header.php" ?>

<div x-data="auth" class="auth" style="background-image: url(assets/img/bg-auth.jpg)">
    <div class="auth-logo mb-2">
        <svg><use href="assets/img/sprite.svg#logo" /></svg>
    </div>
    <form @submit.prevent="enter()">

        <div x-cloak class="form-error text-center mb-2" x-show="error.length > 0" x-text="error" x-transition.opacity></div>

        <div class="col mb-2">
            <input type="password" name="password" x-model="auth.password" placeholder="Введите пароль">
        </div>

        <div class="col mb-2 toggler-wrap" @click="auth.remember = !auth.remember">
            <div class="toggler" :class="auth.remember && '--done'"></div> Запомнить?
        </div>

        <div class="col">
            <button type="submit" class="btn btn--primary btn--fw">Войти</button>
        </div>
    </form>

    <div class="auth-credits">
    Photo by <a href="https://unsplash.com/photos/4yta6mU66dE">Luca Bravo</a> on <a href="https://unsplash.com/">Unsplash</a>
    </div>
</div>
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('auth', () => ({
            auth: {
                action: 'enter',
                password: '',
                remember: 0,
            },
            error: '',

            enter() {
                this.error = ''
                $.post('action.php', this.auth, (function(data) {
                    if (data.error) {
                        this.error = data.msg
                    } else {
                        document.location.reload()
                    }
                }).bind(this), 'json')
            }
        }))
    })
</script>
