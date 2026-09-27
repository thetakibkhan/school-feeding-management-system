<div {{ $attributes->merge(['class' => 'app-shell']) }}>
    <x-theme-toggle />
    <div class="app-shell__background" aria-hidden="true">
        <x-beams-background />
    </div>
    <div class="app-shell__content">
        {{ $slot }}
    </div>
</div>
