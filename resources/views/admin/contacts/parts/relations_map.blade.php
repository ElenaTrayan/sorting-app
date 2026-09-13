{{-- $contact, $mapNodes: Contact::getRelationsAttribute()['map_nodes'] --}}
<div class="b-facts-card b-relations-map-card">
    <div class="b-facts-card__header" style="background: #f4f6f9;">
        <span class="b-facts-card__icon" style="color: #495057;"><i class="fas fa-project-diagram"></i></span>
        <h3>Карта связей</h3>
    </div>
    <div class="b-facts-card__body">
        <div class="b-relations-map">
            <svg viewBox="0 0 100 100" class="b-relations-map__lines" preserveAspectRatio="xMidYMid meet">
                @foreach ($mapNodes as $node)
                    <line x1="50" y1="50" x2="{{ $node['x'] }}" y2="{{ $node['y'] }}" stroke="{{ $node['color'] }}" stroke-width="0.6" opacity="0.45"></line>
                @endforeach
            </svg>

            <div class="b-relations-map__center js-contact-head-photo" @if ($contact->photo_url) style="background-image: url({{ $contact->photo_url }})" @endif>
                @if (!$contact->photo_url)
                    <i class="fas fa-user"></i>
                @endif
            </div>

            @foreach ($mapNodes as $node)
                <div class="b-relations-map__node" style="left: {{ $node['x'] }}%; top: {{ $node['y'] }}%;">
                    <span class="b-relations-map__node-dot" style="background: {{ $node['color'] }};"><i class="{{ $node['icon'] }}"></i></span>
                    <span class="b-relations-map__node-label">{{ $node['title'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
