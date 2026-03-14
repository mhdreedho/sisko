@aware([
    'status' => null,
])

@props([
    'status' => null,
])

@php
$classes = Flux::classes()
    ->add($status === 'incomplete' ? 'opacity-75' : '')
    ;
@endphp

<div {{ $attributes->class($classes) }} data-flux-timeline-content>
   {{ $slot }}
</div>