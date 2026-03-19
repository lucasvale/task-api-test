<x-mail::message>
# Task Updated

Hi {{ $assigneeName }},

A task assigned to you in the project **{{ $projectName }}** has been updated.

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
