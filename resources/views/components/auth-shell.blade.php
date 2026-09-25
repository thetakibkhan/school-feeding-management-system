<div {{ $attributes->merge(['class' => 'auth-shell-layout']) }}>
    <div class="auth-shell-layout__background" aria-hidden="true">
        {{ $background ?? '' }}
    </div>
    <div class="auth-shell-layout__content">
        {{ $slot }}
    </div>
</div>
