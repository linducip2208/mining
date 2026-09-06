@props(['title' => null, 'number' => null, 'watermark' => null])
@php($documentTitle = $title ?: $appName)
@extends('layouts.print')
@section('document')
    <x-print.header :title="$title" :number="$number" />
    {{ $slot }}
    <x-print.footer />
@endsection
