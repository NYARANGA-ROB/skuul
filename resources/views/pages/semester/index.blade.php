@extends('layouts.app', ['breadcrumbs' => [
    ['href'=> route('dashboard'), 'text'=> 'Dashboard'],
<<<<<<< HEAD
    ['href'=> route('semesters.index'), 'text'=> 'Terms', 'active'],
]])

@section('title', __('Terms'))

@section('page_heading',  __('Terms'))
=======
    ['href'=> route('semesters.index'), 'text'=> 'Semesters', 'active'],
]])

@section('title', __('Semesters'))

@section('page_heading',  __('Semesters'))
>>>>>>> e327a8efde9094f66181c9b195fbe5df035baa20

@section('content', ) 
    @livewire('set-semester')

    @livewire('list-semesters-table')
@endsection