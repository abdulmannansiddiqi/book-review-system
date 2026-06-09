<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\{User, Admin, Book, Genre, Review};
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Create admin
        Admin::create([
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('Admin@123'),
            'role' => 'admin'
        ]);

        // Create test user
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password' => Hash::make('Test@123'),
            'bio' => 'I love reading books!'
        ]);

        // Create genres
        $genres = [
            ['name' => 'Fiction', 'description' => 'Imaginative or made-up stories'],
            ['name' => 'Non-Fiction', 'description' => 'Books based on facts and real events'],
            ['name' => 'Science Fiction', 'description' => 'Stories about futuristic science and technology'],
            ['name' => 'Mystery', 'description' => 'Stories involving suspense and crime'],
            ['name' => 'Romance', 'description' => 'Stories about love and relationships'],
            ['name' => 'Fantasy', 'description' => 'Stories with magical and supernatural elements']
        ];

        foreach ($genres as $genre) {
            Genre::create($genre);
        }

        // Create some books
        $books = [
            [
                'title' => 'The Great Adventure',
                'author' => 'John Smith',
                'description' => 'An exciting adventure story',
                'isbn' => '1234567890',
                'publication_year' => 2023,
                'cover_image' => 'default-book.jpg',
                'status' => 'active'
            ],
            [
                'title' => 'Mystery Manor',
                'author' => 'Jane Doe',
                'description' => 'A thrilling mystery novel',
                'isbn' => '0987654321',
                'publication_year' => 2023,
                'cover_image' => 'default-book.jpg',
                'status' => 'active'
            ],
            [
                'title' => 'Future World',
                'author' => 'Robert Johnson',
                'description' => 'A science fiction masterpiece',
                'isbn' => '1122334455',
                'publication_year' => 2023,
                'cover_image' => 'default-book.jpg',
                'status' => 'active'
            ]
        ];

        foreach ($books as $bookData) {
            $book = Book::create($bookData);

            // Attach random genres
            $book->genres()->attach(
                Genre::inRandomOrder()->take(2)->pluck('id')->toArray()
            );

            // // Add some reviews
            // for ($i = 0; $i < 3; $i++) {
            //     Review::create([
            //         'user_id' => 1 + $i,
            //         'book_id' => $book->id,
            //         'content' => 'This is a great book!',
            //         'rating' => rand(3, 5),
            //         'status' => 'approved'
            //     ]);
            // }
        }
    }
}
