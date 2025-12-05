<?php

namespace App\Services;

use App\Models\Customer;

class CustomerService
{
    public function create(array $data)
    {
        return Customer::create($data);
    }

    public function update(Customer $customer, array $data)
    {
        $customer->update($data);
        return $customer->fresh();
    }

    public function delete(Customer $customer)
    {
        return $customer->delete();
    }

    public function addContact(Customer $customer, array $data)
    {
        return $customer->contacts()->create($data);
    }

    public function addAddress(Customer $customer, array $data)
    {
        return $customer->addresses()->create($data);
    }

    public function addNote(Customer $customer, array $data)
    {
        $data['user_id'] = auth()->id();
        return $customer->notes()->create($data);
    }

    public function uploadFile(Customer $customer, array $data)
    {
        $data['uploaded_by'] = auth()->id();
        return $customer->files()->create($data);
    }
}
