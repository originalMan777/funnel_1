<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MediaLibraryController extends Controller
{
    private array $allowedExtensions = [
        'jpg', 'jpeg', 'jpe', 'jfif',
        'png', 'webp', 'gif', 'bmp', 'avif', 'svg',
    ];

    private array $allowedImageMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/bmp',
        'image/avif',
        'image/svg+xml',
    ];

    public function index(Request $request)
    {
        $payload = $this->buildIndexPayload($request);

        return Inertia::render('Media/Index', $payload);
    }

    public function browser(Request $request)
    {
        $payload = $this->buildIndexPayload($request);

        return Inertia::render('Media/Browser', $payload);
    }

    public function feed(Request $request)
    {
        $payload = $this->buildIndexPayload($request);

        return response()->json($payload);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'folder' => ['nullable', 'string', 'max:255'],
            'image' => [
                'required',
                'file',
                'image',
                'mimetypes:' . implode(',', $this->allowedImageMimeTypes),
                'extensions:' . implode(',', $this->allowedExtensions),
                'max:8192',
            ],
        ]);

        $folder = trim((string) ($validated['folder'] ?? 'blog'), '/');

        if ($folder === '') {
            $folder = 'blog';
        }

        if (!in_array($folder, $this->allowedFolderValues(), true)) {
            throw ValidationException::withMessages([
                'folder' => 'Invalid media folder.',
            ]);
        }

        $targetDir = $this->folderPath($folder);

        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $realImagesRoot = realpath($this->imagesRoot());
        $realTargetDir = realpath($targetDir);

        if ($realImagesRoot === false || $realTargetDir === false || !Str::startsWith($realTargetDir, $realImagesRoot)) {
            throw ValidationException::withMessages([
                'folder' => 'Invalid media folder.',
            ]);
        }

        /** @var \Illuminate\Http\UploadedFile $image */
        $image = $validated['image'];

        $originalBase = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $base = Str::slug($originalBase);

        if ($base === '') {
            $base = 'image';
        }

        $extension = strtolower($image->getClientOriginalExtension() ?: $image->guessExtension() ?: 'png');

        if (!in_array($extension, $this->allowedExtensions, true)) {
            $extension = 'png';
        }

        $filename = $base . '.' . $extension;
        $counter = 2;

        while (File::exists($targetDir . DIRECTORY_SEPARATOR . $filename)) {
            $filename = $base . '-' . $counter . '.' . $extension;
            $counter++;
        }

        $image->move($targetDir, $filename);

        $absolutePath = $targetDir . DIRECTORY_SEPARATOR . $filename;
        $publicPath = $folder === '__root__'
            ? '/images/' . $filename
            : '/images/' . $folder . '/' . $filename;

        return response()->json([
            'message' => 'Image uploaded.',
            'item' => [
                'name' => $filename,
                'filename' => $filename,
                'folder' => $folder,
                'path' => $publicPath,
                'url' => $publicPath,
                'size_kb' => round(filesize($absolutePath) / 1024, 1),
                'modified_at' => date('c', filemtime($absolutePath)),
                'extension' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
            ],
        ]);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:2048'],
        ]);

        $publicPath = (string) $validated['path'];

        if (!Str::startsWith($publicPath, '/images/')) {
            return response()->json([
                'message' => 'Only files inside /images can be deleted.',
            ], 422);
        }

        $imagesRoot = $this->imagesRoot();
        $relativePath = ltrim(Str::after($publicPath, '/images/'), '/');

        if ($relativePath === '') {
            return response()->json([
                'message' => 'Invalid file path.',
            ], 422);
        }

        $absolutePath = $imagesRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        $realImagesRoot = realpath($imagesRoot);
        $realTargetDir = realpath(dirname($absolutePath));

        if ($realImagesRoot === false || $realTargetDir === false || !Str::startsWith($realTargetDir, $realImagesRoot)) {
            return response()->json([
                'message' => 'Invalid file path.',
            ], 422);
        }

        if (!File::exists($absolutePath) || !File::isFile($absolutePath)) {
            return response()->json([
                'message' => 'File not found.',
            ], 404);
        }

        $inUse = Post::query()
            ->where('featured_image_path', $publicPath)
            ->orWhere('og_image_path', $publicPath)
            ->exists();

        if ($inUse) {
            return response()->json([
                'message' => 'This image is still being used by a post.',
            ], 422);
        }

        File::delete($absolutePath);

        return response()->json([
            'message' => 'Image deleted.',
        ]);
    }

    private function buildIndexPayload(Request $request): array
    {
        $folder = trim((string) $request->query('folder', 'blog'), '/');
        $search = trim((string) $request->query('search', ''));
        $perPage = max(1, min((int) $request->query('per_page', 24), 100));
        $page = max(1, (int) $request->query('page', 1));
        $debug = $request->boolean('debug');

        $imagesRoot = $this->imagesRoot();

        $folders = $this->folderOptions();
        $allowedFolderValues = $folders->pluck('value')->all();

        if ($folder === '') {
            $folder = 'blog';
        }

        if (!in_array($folder, $allowedFolderValues, true)) {
            $folder = $folders->first()['value'] ?? 'blog';
        }

        $folderPath = $this->folderPath($folder);

        if (!File::isDirectory($folderPath)) {
            File::makeDirectory($folderPath, 0755, true);
        }

        $files = collect(File::files($folderPath))
            ->filter(function (\SplFileInfo $file) {
                return in_array(strtolower($file->getExtension()), $this->allowedExtensions, true);
            })
            ->filter(function (\SplFileInfo $file) use ($search) {
                if ($search === '') {
                    return true;
                }

                return Str::contains(strtolower($file->getFilename()), strtolower($search));
            })
            ->sortByDesc(fn (\SplFileInfo $file) => $file->getMTime())
            ->values();

        $total = $files->count();

        $pageItems = $files
            ->slice(($page - 1) * $perPage, $perPage)
            ->values()
            ->map(function (\SplFileInfo $file) use ($folder) {
                $publicPath = $folder === '__root__'
                    ? '/images/' . $file->getFilename()
                    : '/images/' . $folder . '/' . $file->getFilename();

                return [
                    'name' => $file->getFilename(),
                    'filename' => $file->getFilename(),
                    'folder' => $folder,
                    'path' => $publicPath,
                    'url' => $publicPath,
                    'size_kb' => round($file->getSize() / 1024, 1),
                    'modified_at' => date('c', $file->getMTime()),
                    'extension' => strtolower($file->getExtension()),
                ];
            });

        $media = new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $payload = [
            'folders' => $folders->values(),
            'filters' => [
                'folder' => $folder,
                'search' => $search,
                'per_page' => $perPage,
            ],
            'media' => $media,
        ];

        if ($debug) {
            $payload['debug'] = [
                'images_root' => $imagesRoot,
                'folder_path' => $folderPath,
                'requested_folder' => $request->query('folder', 'blog'),
                'final_folder' => $folder,
                'allowed_extensions' => $this->allowedExtensions,
                'files_seen_in_folder' => collect(File::files($folderPath))
                    ->map(fn (\SplFileInfo $file) => $file->getFilename())
                    ->values(),
            ];
        }

        return $payload;
    }

    private function imagesRoot(): string
    {
        $imagesRoot = public_path('images');

        if (!File::isDirectory($imagesRoot)) {
            File::makeDirectory($imagesRoot, 0755, true);
        }

        return $imagesRoot;
    }

    private function folderOptions()
    {
        $imagesRoot = $this->imagesRoot();

        $folderOptions = collect([
            [
                'value' => '__root__',
                'label' => 'Images Root',
            ],
            [
                'value' => 'blog',
                'label' => 'Blog',
            ],
        ]);

        $subfolders = collect(File::directories($imagesRoot))
            ->map(fn (string $dir) => basename($dir))
            ->reject(fn (string $name) => $name === 'blog')
            ->sort()
            ->values()
            ->map(fn (string $name) => [
                'value' => $name,
                'label' => Str::title(str_replace(['-', '_'], ' ', $name)),
            ]);

        return $folderOptions->concat($subfolders)->values();
    }

    private function allowedFolderValues(): array
    {
        return $this->folderOptions()
            ->pluck('value')
            ->all();
    }

    private function folderPath(string $folder): string
    {
        $imagesRoot = $this->imagesRoot();

        if ($folder === '__root__') {
            return $imagesRoot;
        }

        return $imagesRoot . DIRECTORY_SEPARATOR . $folder;
    }
}
