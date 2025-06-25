<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Amount</th>
        <th>Type</th>
        <th>Status</th>
        <th>Narration</th>
        <th>Created At</th>
    </tr>
    </thead>
    <tbody>
    @foreach($transactions as $transaction)
        <tr>
            <td>{{ $transaction->id }}</td>
            <td>{{ $transaction->amount }}</td>
            <td>{{ $transaction->type }}</td>
            <td>{{ $transaction->status }}</td>
            <td>{{ $transaction->narration }}</td>
            <td>{{ $transaction->created_at }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
