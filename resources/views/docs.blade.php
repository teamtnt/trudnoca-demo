@extends('app')

@section('index')
{!! $index !!}
@endsection

@section('content')

<h1>{!! $title !!}</h1>
<br><br>
{!! $content !!}
@endsection