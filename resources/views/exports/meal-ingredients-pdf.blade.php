<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Meal ingredients</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { font-size: 10px; color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-weight: 600; }
        tr:nth-child(even) { background: #f9fafb; }
    </style>
</head>
<body>
    <h1>Meal ingredients</h1>
    <p class="meta">Generated at {{ $generatedAt->format('Y-m-d H:i:s') }} (UTC)</p>
    <table>
        <thead>
        <tr>
            <th>Meal</th>
            <th>Type</th>
            <th>Partner</th>
            <th>Category</th>
            <th>Details</th>
            <th>Created</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row['meal_title'] }}</td>
                <td>{{ $row['meal_type'] ?? '—' }}</td>
                <td>{{ $row['partner_name'] }}</td>
                <td>{{ $row['meal_category_name'] }}</td>
                <td>{{ $row['metadata_formatted'] }}</td>
                <td>{{ $row['created_at_formatted'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
