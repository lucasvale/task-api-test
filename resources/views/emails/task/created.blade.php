<x-mail::message>
# New Task Assigned

Hi {{ $assigneeName }},

You have been assigned to a new task in the project **{{ $projectName }}**.

| | |
|:--|:--|
| **Task** | {{ $taskTitle }} |
| **Description** | {{ $taskDescription ?? 'No description provided.' }} |
| **Status** | {{ $taskStatus }} |
| **Due Date** | {{ $taskDueDate ?? 'No due date' }} |

<x-mail::button :url="$url">
View Task
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
