# Student CRUD Manual — Design Spec

**Date:** 2026-04-22
**Status:** Approved

## Overview

Add manual create and edit functionality to the existing student management module. Currently, students can only be added via Excel import. This spec covers adding individual students via a form, editing existing student data, and the minor index-page change to surface these actions.

## Scope

- Add `create`, `store`, `edit`, `update` to `StudentController`
- Two new Form Request classes: `StoreStudentRequest`, `UpdateStudentRequest`
- Two new React pages: `students/create.tsx`, `students/edit.tsx`
- Minor update to `students/index.tsx` (add "Tambah Siswa" button)
- New routes in `routes/students.php`

**Out of scope:** password change on edit, guardian linkage, bulk operations, email notifications to the student.

---

## Backend

### Routes (`routes/students.php`)

Four new routes added inside the existing middleware group (`auth`, `verified`, `EnsureTeamMembership::class.':admin'`):

```php
Route::get('students/create',       [StudentController::class, 'create'])->name('create');
Route::post('students',             [StudentController::class, 'store'])->name('store');
Route::get('students/{user}/edit',  [StudentController::class, 'edit'])->name('edit');
Route::patch('students/{user}',     [StudentController::class, 'update'])->name('update');
```

Route order matters: `students/create` must be defined before `students/{user}` to avoid the literal string "create" being treated as a user model binding.

### `StudentController` — new methods

**`create(Request $request): Response`**
- Loads classrooms for the current team (id + name, ordered by name).
- Renders `students/create` with `['classrooms' => ...]`.

**`store(StoreStudentRequest $request): RedirectResponse`**
- Creates `User` with `name`, `email`, `password` (hashed), `email_verified_at = now()`.
- Attaches user to team as `TeamRole::Student`.
- If `classroom_id` provided: creates `StudentEnrollment` with the given `student_number` (nullable).
- Flashes toast: `['type' => 'success', 'message' => 'Siswa berhasil ditambahkan.']`.
- Redirects to `students.index`.

**`edit(Request $request, string $currentTeam, User $user): Response`**
- Aborts 403 if the user is not a student member of the current team.
- Loads classrooms for the current team.
- Loads existing `StudentEnrollment` for this user (if any) to pre-fill classroom + NIS.
- Renders `students/edit` with `['student' => ..., 'classrooms' => ..., 'enrollment' => ...]`.

**`update(UpdateStudentRequest $request, string $currentTeam, User $user): RedirectResponse`**
- Aborts 403 if the user is not a student member of the current team.
- Updates `user.name`, `user.email`.
- If `classroom_id` provided: upsert `StudentEnrollment` (update existing or create new).
- If `classroom_id` is null: delete existing enrollment (if any).
- Updates `student_number` on enrollment when present.
- Flashes toast: `['type' => 'success', 'message' => 'Data siswa berhasil diperbarui.']`.
- Redirects to `students.index`.

### Form Requests

**`StoreStudentRequest`**

| Field | Rules |
|---|---|
| `name` | `required`, `string`, `max:255` |
| `email` | `required`, `email`, `max:255`, `unique:users,email` |
| `password` | `required`, `string`, `min:8`, `max:255` |
| `student_number` | `nullable`, `string`, `max:50` |
| `classroom_id` | `nullable`, `integer`, `exists:classrooms,id` |

**`UpdateStudentRequest`**

| Field | Rules |
|---|---|
| `name` | `required`, `string`, `max:255` |
| `email` | `required`, `email`, `max:255`, `unique:users,email,{user.id}` |
| `student_number` | `nullable`, `string`, `max:50` |
| `classroom_id` | `nullable`, `integer`, `exists:classrooms,id` |

Both Form Requests authorize based on `TeamRole::Admin` (same as controller-level middleware).

---

## Frontend

### `students/index.tsx` — change

Add a "Tambah Siswa" `<Link>` button in `<PageHeader>` actions slot, next to the existing "Import Siswa" button. Uses Wayfinder's `StudentController.create.url(slug)`.

### `students/create.tsx`

```
PageHeader: "Tambah Siswa"  (breadcrumb → students.index)
Form:
  - Nama*        <Input type="text" />
  - Email*       <Input type="email" />
  - Password*    <Input type="password" /> + "Generate" button (fills 12-char random string)
  - NIS          <Input type="text" placeholder="Nomor Induk Siswa" />
  - Kelas        <Select> with "Tidak ada kelas" as default empty option
Buttons: [Simpan] [Batal → students.index]
```

- Uses `useForm()` from `@inertiajs/react`
- Submit: `form.post(StudentController.store.url(slug))`
- Field errors via `<InputError message={form.errors.field} />`

### `students/edit.tsx`

```
PageHeader: "Edit Siswa — {student.name}"  (breadcrumb → students.index)
Form:
  - Nama*        <Input type="text" />  (pre-filled)
  - Email*       <Input type="email" /> (pre-filled)
  - NIS          <Input type="text" />  (pre-filled from enrollment.student_number)
  - Kelas        <Select>               (pre-filled from enrollment.classroom_id)
Buttons: [Perbarui] [Batal → students.index]
```

- No password field.
- Submit: `form.patch(StudentController.update.url({ currentTeam: slug, user: student.id }))`

---

## Data Flow

```
Admin → /students/create
  → fill form → POST /students
  → StudentController::store
    → create User
    → attach to team as Student
    → optional: create StudentEnrollment
  → redirect students.index + toast

Admin → /students/{user}/edit
  → fill form → PATCH /students/{user}
  → StudentController::update
    → update User fields
    → upsert/delete StudentEnrollment
  → redirect students.index + toast
```

---

## Authorization

All new routes sit behind the existing `EnsureTeamMembership::class.':admin'` middleware — no additional policy changes needed. Controllers add inline `abort_if` guards to verify the target user belongs to the current team as a student (consistent with existing `destroy` method).

---

## Testing

Feature tests to add in `tests/Feature/Students/`:

- `StudentCreateTest`: can render create page, can store valid student (with and without classroom), validation rejects duplicate email, validation rejects missing required fields.
- `StudentEditTest`: can render edit page, can update student data, can change classroom, can clear classroom, non-student user returns 403.
- Authorization tests: non-admin cannot access create/store/edit/update routes.
