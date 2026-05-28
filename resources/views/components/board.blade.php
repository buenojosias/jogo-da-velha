@props(['cells'])

<div class="px-6">
    <div id="board" class="board cells-{{ $cells }}">
        @for ($i = 0; $i < $cells; $i++)
        <div class="p-1">
            <div class="cell">
            </div>
        </div>
        @endfor
    </div>
</div>