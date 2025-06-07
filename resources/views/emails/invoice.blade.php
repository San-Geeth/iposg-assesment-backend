<p>
    Dear Valued Customer,<br><br>
    Please find below the summary of payments received for IPOSG as of {{ now()->toDateString() }}.<br>
    The table includes the payment date, reference number, original amount, and the equivalent amount in USD.<br><br>
    If you have any questions or require further details regarding any of the transactions listed,<br>
    please do not hesitate to contact us.<br><br>
    Thank you for your continued partnership.<br><br>
    Best regards,<br>
    Support Team,<br>
    IPOSG
</p>

<table border="1" cellpadding="8" cellspacing="0" width="100%">
    <thead>
    <tr>
        <th>Payment Date</th>
        <th>Reference</th>
        <th>Amount (Original)</th>
        <th>Amount (USD)</th>
    </tr>
    </thead>
    <tbody>
    @php $total = 0; @endphp
    @foreach ($payments as $payment)
        <tr>
            <td>{{ $payment->created_at->toDateString() }}</td>
            <td>{{ $payment->reference_no }}</td>
            <td>{{ $payment->currency }} {{ $payment->amount }}</td>
            <td>USD {{ $payment->usd_amount }}</td>
        </tr>
        @php $total += $payment->usd_amount; @endphp
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <td colspan="3"><strong>Total</strong></td>
        <td><strong>USD {{ number_format($total, 2) }}</strong></td>
    </tr>
    </tfoot>
</table>
