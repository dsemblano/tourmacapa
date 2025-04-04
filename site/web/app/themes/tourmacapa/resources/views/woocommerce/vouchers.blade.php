@if($vouchers = get_posts([
  'post_type' => 'voucher',
  'meta_key' => 'customer_id',
  'meta_value' => get_current_user_id(),
  'posts_per_page' => -1
]))
  <div class="space-y-4">
    @foreach($vouchers as $voucher)
      <div class="p-4 border rounded-lg">
        <div class="flex items-start space-x-4">
          <div class="flex-shrink-0">
            <img src="{{ get_field('qr_code', $voucher->ID) }}" 
                 class="w-32 h-32" 
                 alt="Voucher QR Code">
          </div>
          <div>
            <h3 class="font-bold">{{ $voucher->post_title }}</h3>
            <p class="text-sm text-gray-600">
              Status: <span class="px-2 py-1 rounded-full 
              @if(get_field('voucher_status', $voucher->ID) === 'active')
                bg-green-100 text-green-800
              @else
                bg-gray-100 text-p
              @endif">
                {{ ucfirst(get_field('voucher_status', $voucher->ID)) }}
              </span>
            </p>
            <p class="mt-2 font-mono text-sm">{{ get_field('voucher_code', $voucher->ID) }}</p>
          </div>
        </div>
      </div>
    @endforeach
  </div>
@else
  <p>No active vouchers found.</p>
@endif