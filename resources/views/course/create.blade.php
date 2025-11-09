@extends('layouts.app')

@section('content')
<div class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
    <main class="flex max-w-[335px] w-full flex-col lg:max-w-4xl">
        <div class="text-[13px] leading-[20px] p-6 pb-12 lg:p-20 bg-white dark:bg-[#161615] dark:text-[#EDEDEC] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] rounded-lg h-full">
            <h1 class="mb-4 text-2xl font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Create New Course</h1>

            <div class="min-h-[600px]">
                <form method="POST" action="{{ route('courses.store') }}" class="flex flex-col gap-4">
                    @csrf

                    <div class="flex flex-col gap-2">
                        <label for="name" class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                            Course Name
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            class="px-4 py-3 text-[13px] leading-[20px] bg-[#FDFDFC] dark:bg-[#0a0a0a] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:border-[#1915014a] dark:focus:border-[#62605b] transition-all"
                            placeholder="Enter course name"
                        />
                        @error('name')
                            <p class="text-sm text-[#F53003] dark:text-[#FF4433]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="description" class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                            Course Description
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="6"
                            required
                            class="px-4 py-3 text-[13px] leading-[20px] bg-[#FDFDFC] dark:bg-[#0a0a0a] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:border-[#1915014a] dark:focus:border-[#62605b] transition-all resize-vertical"
                            placeholder="Enter course description"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-sm text-[#F53003] dark:text-[#FF4433]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-3 mt-4">
                        <button
                            type="submit"
                            class="inline-block px-5 py-2 text-white dark:text-[#1C1C1A] bg-[#1b1b18] dark:bg-[#eeeeec] border border-[#1b1b18] dark:border-[#eeeeec] hover:bg-black dark:hover:bg-white hover:border-black dark:hover:border-white rounded-sm text-sm leading-normal transition-all"
                        >
                            Create Course
                        </button>
                        <a
                            href="{{ route('courses') }}"
                            class="inline-block px-5 py-2 text-[#1b1b18] dark:text-[#EDEDEC] border border-[#19140035] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal transition-all"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
@endsection
