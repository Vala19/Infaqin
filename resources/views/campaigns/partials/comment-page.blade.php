@foreach ($comments as $comment)
    <x-prayer-card :comment="$comment" :amened="in_array($comment->id, $amenedIds)" />
@endforeach
<span hidden data-next-url="{{ $comments->nextPageUrl() }}"></span>
