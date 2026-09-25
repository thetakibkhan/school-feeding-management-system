@props(['intensity' => 'strong'])

<div {{ $attributes->merge(['class' => 'beams-background']) }} data-beams-background data-intensity="{{ $intensity }}">
    <canvas data-beams-canvas aria-hidden="true"></canvas>
    <div class="beams-background__veil" aria-hidden="true" data-beams-overlay></div>
    <div class="beams-background__content">
        {{ $slot }}
    </div>
</div>
