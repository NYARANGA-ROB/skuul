@extends('layouts.app', ['breadcrumbs' => [
    ['href'=> route('dashboard'), 'text'=> 'Dashboard'],
<<<<<<< HEAD
    ['href'=> route('semesters.index'), 'text'=> 'Terms' , ],
=======
    ['href'=> route('semesters.index'), 'text'=> 'semesters' , ],
>>>>>>> e327a8efde9094f66181c9b195fbe5df035baa20
    ['href'=> route('semesters.edit', $semester->id), 'text'=> "Edit $semester->name" , 'active']
]])
@section('title', __("Edit $semester->name"))

@section('page_heading',  __("Edit $semester->name"))

@section('content')
    @livewire('edit-semester-form', ['semester' => $semester]
)@endsection
