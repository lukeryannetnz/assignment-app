<!DOCTYPE html>
<html>
    <head>
        <title>courses</title>
    </head>
    <body>
        <div>
            hello
        </div>
        @if ($courses->isEmpty())
            <div>No courses.</div>
        @else
            <ul>
                @foreach ( $courses as $course )
                    <li>
                        <h3>{{ $course->name }}</h3>
                        <p>{{ $course->description }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </body>
</html>