@if(is_array($fieldValue))
@foreach($fieldValue as $index=>$nested)
@include('workflows.hidden',['fieldName'=>$fieldName.'['.$index.']','fieldValue'=>$nested])
@endforeach
@else
<input type="hidden" name="{{ $fieldName }}" value="{{ is_bool($fieldValue)?(int)$fieldValue:$fieldValue }}">
@endif
