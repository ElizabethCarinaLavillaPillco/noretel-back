<?php

namespace Modules\Core\Repositories;

use Modules\Core\Entities\User;

class EmployeeRepository
{
    /**
     * Get all employees
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all()
    {
        return User::orderBy('first_name')->get();
    }

    /**
     * Find employees by position/role
     *
     * @param string $position
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByPosition($position)
    {
        // Buscar usuarios con el rol especificado
        return User::whereHas('roles', function ($query) use ($position) {
            $query->where('name', $position)
                  ->orWhere('name', 'like', "%{$position}%");
        })->orderBy('first_name')->get();
    }

    /**
     * Find an employee by ID
     *
     * @param int $id
     * @return User
     */
    public function find($id)
    {
        return User::findOrFail($id);
    }

    /**
     * Get active employees
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActive()
    {
        return User::where('active', true)
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Get employees by department
     *
     * @param string $department
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByDepartment($department)
    {
        return User::where('department', $department)
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Search employees
     *
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search($search)
    {
        return User::where(function ($query) use ($search) {
            $query->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        })->orderBy('first_name')->get();
    }

    /**
     * Create a new employee
     *
     * @param array $data
     * @return User
     */
    public function create(array $data)
    {
        return User::create($data);
    }

    /**
     * Update an employee
     *
     * @param int $id
     * @param array $data
     * @return User
     */
    public function update($id, array $data)
    {
        $employee = $this->find($id);
        $employee->update($data);
        return $employee;
    }

    /**
     * Delete an employee
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $employee = $this->find($id);
        return $employee->delete();
    }

    /**
     * Get query builder
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return User::query();
    }
}
