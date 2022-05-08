<?php
namespace App\Transformers;

use App\Provider;
use League\Fractal\TransformerAbstract;

class ProviderTransformer extends TransformerAbstract
{
    protected $availableIncludes = [
        'user'
    ];

    public function transform(Provider $provider)
    {
        return [
            'provider_id' => $provider->id,
        ];
    }

    public function includeUser(Provider $provider)
    {
        return $this->item($provider->user, \App::make(UserTransformer::class));
    }
}
?>