
@extends('layouts.app')
@include('partials.nav')
<div class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
    <main class="flex max-w-[335px] w-full flex-col lg:max-w-4xl">
        <div class="text-[13px] leading-[20px] p-6 pb-12 lg:p-20 bg-white dark:bg-[#161615] dark:text-[#EDEDEC] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] rounded-lg h-full">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-medium text-[#1b1b18] dark:text-[#EDEDEC]">All Courses</h1>
                <a
                    href="{{ route('courses.create') }}"
                    class="inline-block px-5 py-2 text-white dark:text-[#1C1C1A] bg-[#1b1b18] dark:bg-[#eeeeec] border border-[#1b1b18] dark:border-[#eeeeec] hover:bg-black dark:hover:bg-white hover:border-black dark:hover:border-white rounded-sm text-sm leading-normal transition-all"
                >
                    Create New Course
                </a>
            </div>

            <div  class="min-h-[600px]">
                @if ($courses->isEmpty())
                    <div class="p-6 bg-[#fff2f2] dark:bg-[#1D0002] rounded-sm border border-[#e3e3e0] dark:border-[#3E3E3A]">
                        <p class="text-[#706f6c] dark:text-[#A1A09A]">No courses available.</p>
                    </div>
                @else
                    <ul class="flex flex-col gap-4 mb-4">
                        @foreach ( $courses as $course )
                            <li class="p-6 bg-[#FDFDFC] dark:bg-[#0a0a0a] rounded-sm border border-[#e3e3e0] dark:border-[#3E3E3A] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] hover:border-[#1915014a] dark:hover:border-[#62605b] transition-all">
                                <a href="{{ route('courses.edit', $course->id) }}" class="block">
                                    <h3 class="mb-2 text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{{ $course->name }}</h3>
                                    <p class="text-[#706f6c] dark:text-[#A1A09A]">{{ $course->description }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    {{ $courses->links() }}
                @endif
            </div>
        </div>
    </main>
</div>
