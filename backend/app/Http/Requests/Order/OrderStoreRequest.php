<?php
namespace App\Http\Requests\Order;

use App\Enums\OrderSide;
use App\Models\AssetType;
use App\Rules\ValidAmountPrecision;
use App\Rules\ValidPricePrecision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'symbol' => [
                'required',
                Rule::exists(AssetType::class, 'symbol'),
            ],

            'side'   => [
                'required',
                Rule::enum(OrderSide::class),
            ],

            'price'  => [
                'required',
                'numeric',
                new ValidPricePrecision(),
            ],

            'amount' => [
                'required',
                'numeric',
                new ValidAmountPrecision(),
            ],
        ];
    }

    public function normalized(): array
    {
        $symbol = strtoupper($this->validated('symbol'));
        $asset  = AssetType::findOrFail($symbol);
        $scale  = $asset->atomic_scale;

        $priceScale = 8;

        return [
            'symbol'              => $symbol,
            'side'                => $this->validated('side'),

            'price_scale'         => $priceScale,
            'price_numerator'     => (int) bcmul($this->validated('price'), bcpow('10', $priceScale), 0),

            'amount_atomic_units' => (int) bcmul($this->validated('amount'), bcpow('10', $scale), 0),

            'idempotency_key'     => $this->header('Idempotency-Key'),
        ];
    }
}
