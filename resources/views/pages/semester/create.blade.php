@extends('layouts.app', ['breadcrumbs' => [
    ['href'=> route('dashboard'), 'text'=> 'Dashboard'],
<<<<<<< HEAD
    ['href'=> route('semesters.index'), 'text'=> 'Terms'],
    ['href'=> route('semesters.create'), 'text'=> 'Create' , 'active'],
]])

@section('title', __('Create Term'))

@section('page_heading',  __('Create Term'))
=======
    ['href'=> route('semesters.index'), 'text'=> 'Semesters'],
    ['href'=> route('semesters.create'), 'text'=> 'Create' , 'active'],
]])

@section('title', __('Create semester'))

@section('page_heading',  __('Create semester'))
>>>>>>> e327a8efde9094f66181c9b195fbe5df035baa20

@section('content' )
    @livewire('create-semester-form')
@endsection