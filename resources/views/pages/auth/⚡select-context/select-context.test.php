<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('pages::auth.select-context')
        ->assertStatus(200);
});
