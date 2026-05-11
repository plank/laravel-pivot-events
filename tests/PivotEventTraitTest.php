<?php

use Plank\LaravelPivotEvents\Tests\Models\Post;
use Plank\LaravelPivotEvents\Tests\Models\Role;
use Plank\LaravelPivotEvents\Tests\Models\Seller;
use Plank\LaravelPivotEvents\Tests\Models\Tag;
use Plank\LaravelPivotEvents\Tests\Models\User;
use Plank\LaravelPivotEvents\Tests\Models\Video;

function startListening(): void
{
    \Plank\LaravelPivotEvents\Tests\TestCase::$events = [];
}

function events(): array
{
    return \Plank\LaravelPivotEvents\Tests\TestCase::$events;
}

function checkEvents(array $expectedEvents): void
{
    $events = events();
    foreach ($expectedEvents as $i => $event) {
        expect($events[$i]['name'])->toBe($event);
    }
    expect($events)->toHaveCount(count($expectedEvents));
}

function checkVariables(int $number, array $ids, array $idsAttributes = [], string $relation = 'roles'): void
{
    $events = events();
    expect($events[$number]['pivotIds'])->toBe($ids);
    expect($events[$number]['pivotIdsAttributes'])->toEqual($idsAttributes);
    expect($events[$number]['relation'])->toBe($relation);
}

function checkDatabase(int $count, mixed $value, int $number = 0, string $attribute = 'value', string $table = 'role_user'): void
{
    expect(DB::table($table)->get()->get($number)->$attribute)->toBe($value);
    expect(DB::table($table)->count())->toBe($count);
}

// Attach tests

test('attach int', function () {
    startListening();
    $user = User::find(1);
    $user->roles()->attach(1, ['value' => 123]);

    checkEvents(['eloquent.pivotAttaching: '.User::class, 'eloquent.pivotAttached: '.User::class]);
    checkVariables(0, [1], [1 => ['value' => 123]]);
    checkDatabase(1, 123, 0, 'value');
});

test('polymorphic attach int', function () {
    startListening();
    $post = Post::find(1);
    $post->tags()->attach(1, ['value' => 123]);

    checkEvents(['eloquent.pivotAttaching: '.Post::class, 'eloquent.pivotAttached: '.Post::class]);
    checkVariables(0, [1], [1 => ['value' => 123]], 'tags');
    checkDatabase(1, 123, 0, 'value', 'taggables');
});

test('attach string', function () {
    startListening();
    $user = User::find(1);
    $seller = Seller::first();
    $user->sellers()->attach($seller->id, ['value' => 123]);

    checkEvents(['eloquent.pivotAttaching: '.User::class, 'eloquent.pivotAttached: '.User::class]);
    checkVariables(0, [$seller->id], [$seller->id => ['value' => 123]], 'sellers');
    checkDatabase(1, 123, 0, 'value', 'seller_user');
});

test('attach array', function () {
    startListening();
    $user = User::find(1);
    $user->roles()->attach([1 => ['value' => 123], 2 => ['value' => 456]], ['value2' => 789]);

    expect(DB::table('role_user')->count())->toBe(2);
    checkEvents(['eloquent.pivotAttaching: '.User::class, 'eloquent.pivotAttached: '.User::class]);
    checkVariables(0, [1, 2], [1 => ['value' => 123, 'value2' => 789], 2 => ['value' => 456, 'value2' => 789]]);
    checkDatabase(2, 123);
    checkDatabase(2, 789, 0, 'value2');
});

test('polymorphic attach array', function () {
    startListening();
    $video = Video::find(1);
    $video->tags()->attach([1 => ['value' => 123], 2 => ['value' => 456]], ['value2' => 789]);

    expect(DB::table('taggables')->count())->toBe(2);
    checkEvents(['eloquent.pivotAttaching: '.Video::class, 'eloquent.pivotAttached: '.Video::class]);
    checkVariables(0, [1, 2], [1 => ['value' => 123, 'value2' => 789], 2 => ['value' => 456, 'value2' => 789]], 'tags');
    checkDatabase(2, 123, 0, 'value', 'taggables');
    checkDatabase(2, 789, 0, 'value2', 'taggables');
});

test('attach model', function () {
    startListening();
    $user = User::find(1);
    $role = Role::find(1);
    $user->roles()->attach($role, ['value' => 123]);

    expect(DB::table('role_user')->count())->toBe(1);
    checkEvents(['eloquent.pivotAttaching: '.User::class, 'eloquent.pivotAttached: '.User::class]);
    checkVariables(0, [1], [1 => ['value' => 123]]);
    checkDatabase(1, 123);
});

test('polymorphic attach model', function () {
    startListening();
    $tag = Tag::find(1);
    $video = Video::find(1);
    $tag->videos()->attach($video, ['value' => 123]);

    expect(DB::table('taggables')->count())->toBe(1);
    checkEvents(['eloquent.pivotAttaching: '.Tag::class, 'eloquent.pivotAttached: '.Tag::class]);
    checkVariables(0, [1], [1 => ['value' => 123]], 'videos');
    checkDatabase(1, 123, 0, 'value', 'taggables');
});

test('attach collection', function () {
    startListening();
    $user = User::find(1);
    $roles = Role::take(2)->get();
    $user->roles()->attach($roles, ['value' => 123]);

    expect(DB::table('role_user')->count())->toBe(2);
    checkEvents(['eloquent.pivotAttaching: '.User::class, 'eloquent.pivotAttached: '.User::class]);
    checkVariables(0, [1, 2], [1 => ['value' => 123], 2 => ['value' => 123]]);
    checkDatabase(2, 123);
    checkDatabase(2, 123, 1);
});

test('polymorphic attach collection', function () {
    startListening();
    $post = Post::find(1);
    $tags = Tag::take(2)->get();
    $post->tags()->attach($tags, ['value' => 123]);

    expect(DB::table('taggables')->count())->toBe(2);
    checkEvents(['eloquent.pivotAttaching: '.Post::class, 'eloquent.pivotAttached: '.Post::class]);
    checkVariables(0, [1, 2], [1 => ['value' => 123], 2 => ['value' => 123]], 'tags');
    checkDatabase(2, 123, 0, 'value', 'taggables');
    checkDatabase(2, 123, 1, 'value', 'taggables');
});

// Detach tests

test('detach int', function () {
    startListening();
    $user = User::find(1);
    $user->roles()->attach([1, 2, 3]);
    expect(DB::table('role_user')->count())->toBe(3);

    startListening();
    $user->roles()->detach(2);

    expect(DB::table('role_user')->count())->toBe(2);
    checkEvents(['eloquent.pivotDetaching: '.User::class, 'eloquent.pivotDetached: '.User::class]);
    checkVariables(0, [2]);
});

test('polymorphic detach int', function () {
    startListening();
    $video = Video::find(1);
    $video->tags()->attach([1, 2, 3]);
    expect(DB::table('taggables')->count())->toBe(3);

    startListening();
    $video->tags()->detach(2);

    expect(DB::table('taggables')->count())->toBe(2);
    checkEvents(['eloquent.pivotDetaching: '.Video::class, 'eloquent.pivotDetached: '.Video::class]);
    checkVariables(0, [2], [], 'tags');
});

test('detach array', function () {
    startListening();
    $user = User::find(1);
    $user->roles()->attach([1, 2, 3]);
    expect(DB::table('role_user')->count())->toBe(3);

    startListening();
    $user->roles()->detach([2, 3]);

    expect(DB::table('role_user')->count())->toBe(1);
    checkEvents(['eloquent.pivotDetaching: '.User::class, 'eloquent.pivotDetached: '.User::class]);
    checkVariables(0, [2, 3]);
});

test('polymorphic detach array', function () {
    startListening();
    $post = Post::find(1);
    $post->tags()->attach([1, 2, 3]);
    expect(DB::table('taggables')->count())->toBe(3);

    startListening();
    $post->tags()->detach([2, 3]);

    expect(DB::table('taggables')->count())->toBe(1);
    checkEvents(['eloquent.pivotDetaching: '.Post::class, 'eloquent.pivotDetached: '.Post::class]);
    checkVariables(0, [2, 3], [], 'tags');
});

test('detach model', function () {
    startListening();
    $user = User::find(1);
    $user->roles()->attach([1, 2, 3]);
    expect(DB::table('role_user')->count())->toBe(3);

    startListening();
    $role = Role::find(1);
    $user->roles()->detach($role);

    expect(DB::table('role_user')->count())->toBe(2);
    checkEvents(['eloquent.pivotDetaching: '.User::class, 'eloquent.pivotDetached: '.User::class]);
    checkVariables(0, [1]);
});

test('polymorphic detach model', function () {
    startListening();
    $post = Post::find(1);
    $video = Video::find(1);
    $post->tags()->attach([1, 2]);
    $video->tags()->attach([2]);
    expect(DB::table('taggables')->count())->toBe(3);

    startListening();
    $tag = Tag::find(2);
    $tag->videos()->detach($video);

    expect(DB::table('taggables')->count())->toBe(2);
    checkEvents(['eloquent.pivotDetaching: '.Tag::class, 'eloquent.pivotDetached: '.Tag::class]);
    checkVariables(0, [1], [], 'videos');
});

test('detach collection', function () {
    startListening();
    $user = User::find(1);
    $user->roles()->attach([1, 2, 3]);
    expect(DB::table('role_user')->count())->toBe(3);

    startListening();
    $roles = Role::take(2)->get();
    $user->roles()->detach($roles);

    expect(DB::table('role_user')->count())->toBe(1);
    checkEvents(['eloquent.pivotDetaching: '.User::class, 'eloquent.pivotDetached: '.User::class]);
    checkVariables(0, [1, 2]);
});

test('polymorphic detach collection', function () {
    startListening();
    $post = Post::find(1);
    $post->tags()->attach([1, 2, 3]);
    expect(DB::table('taggables')->count())->toBe(3);

    startListening();
    $tags = Tag::take(2)->get();
    $post->tags()->detach($tags);

    expect(DB::table('taggables')->count())->toBe(1);
    checkEvents(['eloquent.pivotDetaching: '.Post::class, 'eloquent.pivotDetached: '.Post::class]);
    checkVariables(0, [1, 2], [], 'tags');
});

test('detach null', function () {
    startListening();
    $user = User::find(1);
    $user->roles()->attach([1, 2, 3]);
    expect(DB::table('role_user')->count())->toBe(3);

    startListening();
    $user->roles()->detach();

    expect(DB::table('role_user')->count())->toBe(0);
    checkEvents(['eloquent.pivotDetaching: '.User::class, 'eloquent.pivotDetached: '.User::class]);
    checkVariables(0, [1, 2, 3]);
});

test('polymorphic detach null', function () {
    startListening();
    $post = Post::find(1);
    $post->tags()->attach([1, 2]);
    $video = Video::find(2);
    $video->tags()->attach([2, 3]);
    expect(DB::table('taggables')->count())->toBe(4);

    startListening();
    $post->tags()->detach();

    expect(DB::table('taggables')->count())->toBe(2);
    checkEvents(['eloquent.pivotDetaching: '.Post::class, 'eloquent.pivotDetached: '.Post::class]);
    checkVariables(0, [1, 2], [], 'tags');
});

// Update tests

test('update existing pivot', function () {
    startListening();
    $user = User::find(1);
    $user->roles()->attach([1, 2, 3]);

    startListening();
    $user->roles()->updateExistingPivot(1, ['value' => 123]);

    expect(DB::table('role_user')->count())->toBe(3);
    checkEvents(['eloquent.pivotUpdating: '.User::class, 'eloquent.pivotUpdated: '.User::class]);
    checkVariables(0, [1], [1 => ['value' => 123]]);
    checkDatabase(3, 123, 0);
    checkDatabase(3, null, 2);
});

test('polymorphic update existing pivot', function () {
    startListening();
    $video = Video::find(1);
    $video->tags()->attach([1, 2, 3]);

    startListening();
    $video->tags()->updateExistingPivot(1, ['value' => 123]);

    expect(DB::table('taggables')->count())->toBe(3);
    checkEvents(['eloquent.pivotUpdating: '.Video::class, 'eloquent.pivotUpdated: '.Video::class]);
    checkVariables(0, [1], [1 => ['value' => 123]], 'tags');
    checkDatabase(3, 123, 0, 'value', 'taggables');
    checkDatabase(3, null, 2, 'value', 'taggables');
});

// Sync tests

test('sync int', function () {
    startListening();
    $user = User::find(1);
    $user->roles()->attach([2, 3]);
    expect(DB::table('role_user')->count())->toBe(2);

    startListening();
    $user->roles()->sync(1);

    expect(DB::table('role_user')->count())->toBe(1);
    checkEvents([
        'eloquent.pivotSyncing: '.User::class,
        'eloquent.pivotSynced: '.User::class,
    ]);
});

test('polymorphic sync int', function () {
    startListening();
    $post = Post::find(1);
    $post->tags()->attach([2, 3]);
    expect(DB::table('taggables')->count())->toBe(2);

    startListening();
    $post->tags()->sync(1);

    expect(DB::table('taggables')->count())->toBe(1);
    checkEvents([
        'eloquent.pivotSyncing: '.Post::class,
        'eloquent.pivotSynced: '.Post::class,
    ]);
});

test('sync array', function () {
    startListening();
    $user = User::find(1);
    $user->roles()->attach([2, 3]);
    expect(DB::table('role_user')->count())->toBe(2);

    startListening();
    $user->roles()->sync([1]);

    expect(DB::table('role_user')->count())->toBe(1);
    checkEvents([
        'eloquent.pivotSyncing: '.User::class,
        'eloquent.pivotSynced: '.User::class,
    ]);
});

test('polymorphic sync array', function () {
    startListening();
    $video = Video::find(1);
    $video->tags()->attach([2, 3]);
    expect(DB::table('taggables')->count())->toBe(2);

    startListening();
    $video->tags()->sync([1]);

    expect(DB::table('taggables')->count())->toBe(1);
    checkEvents([
        'eloquent.pivotSyncing: '.Video::class,
        'eloquent.pivotSynced: '.Video::class,
    ]);
});

test('sync model', function () {
    startListening();
    $user = User::find(1);
    $user->roles()->attach([2, 3]);
    expect(DB::table('role_user')->count())->toBe(2);

    startListening();
    $role = Role::find(1);
    $user->roles()->sync($role);

    checkEvents([
        'eloquent.pivotSyncing: '.User::class,
        'eloquent.pivotSynced: '.User::class,
    ]);
    expect(events())->toHaveCount(2);
});

test('polymorphic sync model', function () {
    startListening();
    $video = Video::find(1);
    $video->tags()->attach([2, 3]);
    expect(DB::table('taggables')->count())->toBe(2);

    startListening();
    $tag = Tag::find(1);
    $video->tags()->sync($tag);

    expect(DB::table('taggables')->count())->toBe(1);
    checkEvents([
        'eloquent.pivotSyncing: '.Video::class,
        'eloquent.pivotSynced: '.Video::class,
    ]);
    expect(events())->toHaveCount(2);
});

test('sync collection', function () {
    startListening();
    $user = User::find(1);
    $user->roles()->attach([1, 2]);
    expect(DB::table('role_user')->count())->toBe(2);

    startListening();
    $roles = Role::whereIn('id', [3, 4])->get();
    $user->roles()->sync($roles);

    checkEvents([
        'eloquent.pivotSyncing: '.User::class,
        'eloquent.pivotSynced: '.User::class,
    ]);
});

test('polymorphic sync collection', function () {
    startListening();
    $tag = Tag::find(1);
    $tag->posts()->attach([1]);
    $tag->videos()->attach([2]);
    expect(DB::table('taggables')->count())->toBe(2);

    startListening();
    $posts = Post::where('id', 2)->get();
    $tag->posts()->sync($posts);

    checkEvents([
        'eloquent.pivotSyncing: '.Tag::class,
        'eloquent.pivotSynced: '.Tag::class,
    ]);
});

// Non-pivot event tests

test('standard update does not fire pivot events', function () {
    startListening();
    $user = User::find(1);

    startListening();
    $user->update(['name' => 'different']);

    expect(events())->toBeEmpty();
});

test('relation is null on standard update', function () {
    startListening();
    $user = User::find(1);
    $user->update(['name' => 'new_name']);

    expect(events())->toBeEmpty();
});
