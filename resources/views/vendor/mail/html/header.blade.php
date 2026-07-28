@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ url('/images/my-wallet.png') }}" class="logo" alt="{{ config('app.name') }}">
</a>
</td>
</tr>
