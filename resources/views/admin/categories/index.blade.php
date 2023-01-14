@extends('layouts.admin_layout')

@section('title', 'Все категории')

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Все категории</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Dashboard v1</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="card posts-categories-index" id="posts-categories-index">

                @if (session('success'))
                    @push('footer-scripts')
                        <script>
                            toastr.success('{{ session('success') }}');
                        </script>
                    @endpush
                @elseif(session('errors'))
                    @push('footer-scripts')
                        <script>
                            toastr.error('{{ session('errors') }}');
                        </script>
                    @endpush
                @endif

                @include('admin.includes.modal_delete_item')

                <div class="card-header" style="display: flex; justify-content: end;">
                    <a href="{{ route('posts-categories.create') }}" class="btn btn-block btn-outline-info" style="width: 180px;">Добавить категорию</a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped projects" data-url="" data-id="">
                        <thead>
                        <tr>
                            <th style="width: 1%">
                                Id
                            </th>
                            <th style="width: 20%">
                                Название категории
                            </th>
                            <th style="width: 20%">
                                Alias
                            </th>
                            <th style="width: 20%">
                                Категория-родитель
                            </th>
                            <th style="width: 5%" class="text-center">
                                Сортировка
                            </th>
                            <th style="width: 8%" class="text-center">
                                Status
                            </th>
                            <th style="width: 15%">
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($categories as $category)
                            <tr data-id="{{ $category->id }}" data-url="{{ route('posts-categories.destroy', $category->id) }}">
                                <td>
                                    {{ $category->id }}
                                </td>
                                <td>
                                    {{ $category->title }}
                                </td>
                                <td>
                                    {{ $category->alias }}
                                </td>
                                <td>
                                    @if (!empty($category->parent))
                                        {{ $category->parent->title }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    {{ $category->sort }}
                                </td>
                                <td class="project-state">
                                    <span class="badge badge-success">{{ $category->status }}</span>
                                </td>
                                <td class="project-actions text-right">
                                    <a class="btn btn-primary btn-sm"
                                       href="{{ route('posts-categories.show', $category->id) }}">
                                        <i class="fas fa-eye">
                                        </i>
                                    </a>
                                    <a class="btn btn-info btn-sm"
                                       href="{{ route('posts-categories.edit', $category->id) }}">
                                        <i class="fas fa-pencil-alt">
                                        </i>
                                    </a>
                                    <button class="btn btn-danger btn-sm"
                                          data-action="delete"
                                          data-url="{{ route('posts-categories.destroy', $category->id) }}"
                                          data-page-id="posts-categories"
                                          tabindex="0"
                                    >
                                        <i class="fas fa-trash">
                                        </i>
                                    </button>
                                    {{--                                <form action="{{ route('posts-categories.destroy', $category->id) }}" method="POST">--}}
                                    {{--                                    @csrf--}}
                                    {{--                                    @method('DELETE')--}}
                                    {{--                                    <button type="submit" class="btn btn-danger btn-sm">--}}
                                    {{--                                        <i class="fas fa-trash">--}}
                                    {{--                                        </i>--}}
                                    {{--                                    </button>--}}
                                    {{--                                </form>--}}
                                </td>
                            </tr>

                            @if (!empty($category->children))
                                @foreach($category->children as $childrenCategory)
                                    <tr data-id="{{ $childrenCategory->id }}" data-url="{{ route('posts-categories.destroy', $childrenCategory->id) }}">
                                        <td>
                                            {{ $childrenCategory->id }}
                                        </td>
                                        <td>
                                            {{ $childrenCategory->title }}
                                        </td>
                                        <td>
                                            {{ $childrenCategory->alias }}
                                        </td>
                                        <td>
                                            {{ $category->title }}
                                        </td>
                                        <td>
                                            {{ $childrenCategory->sort }}
                                        </td>
                                        <td class="project-state">
                                            <span class="badge badge-success">{{ $childrenCategory->status }}</span>
                                        </td>
                                        <td class="project-actions text-right">
                                            <a class="btn btn-primary btn-sm"
                                               href="{{ route('posts-categories.show', $childrenCategory->id) }}">
                                                <i class="fas fa-eye">
                                                </i>
                                            </a>
                                            <a class="btn btn-info btn-sm"
                                               href="{{ route('posts-categories.edit', $childrenCategory->id) }}">
                                                <i class="fas fa-pencil-alt">
                                                </i>
                                            </a>
                                            <button class="btn btn-danger btn-sm"
                                                  data-action="delete"
                                                  data-url="{{ route('posts-categories.destroy', $childrenCategory->id) }}"
                                                    tabindex="0"
                                               >
                                                <i class="fas fa-trash">
                                                </i>
                                            </button>
                                            {{--                                <form action="{{ route('posts-categories.destroy', $category->id) }}" method="POST">--}}
                                            {{--                                    @csrf--}}
                                            {{--                                    @method('DELETE')--}}
                                            {{--                                    <button type="submit" class="btn btn-danger btn-sm">--}}
                                            {{--                                        <i class="fas fa-trash">--}}
                                            {{--                                        </i>--}}
                                            {{--                                    </button>--}}
                                            {{--                                </form>--}}
                                        </td>
                                    </tr>

                                    @if (!empty($childrenCategory->children))
                                        @foreach($childrenCategory->children as $childrenСhildrenCategory)
                                            <tr data-id="{{ $childrenСhildrenCategory->id }}" data-url="{{ route('posts-categories.destroy', $childrenСhildrenCategory->id) }}">
                                                <td>
                                                    {{ $childrenСhildrenCategory->id }}
                                                </td>
                                                <td>
                                                    {{ $childrenСhildrenCategory->title }}
                                                </td>
                                                <td>
                                                    {{ $childrenСhildrenCategory->alias }}
                                                </td>
                                                <td>
                                                    {{ $childrenCategory->title }}
                                                </td>
                                                <td>
                                                    {{ $childrenСhildrenCategory->sort }}
                                                </td>
                                                <td class="project-state">
                                                    <span class="badge badge-success">{{ $childrenСhildrenCategory->status }}</span>
                                                </td>
                                                <td class="project-actions text-right">
                                                    <a class="btn btn-primary btn-sm"
                                                       href="{{ route('posts-categories.show', $childrenСhildrenCategory->id) }}">
                                                        <i class="fas fa-eye">
                                                        </i>
                                                    </a>
                                                    <a class="btn btn-info btn-sm"
                                                       href="{{ route('posts-categories.edit', $childrenСhildrenCategory->id) }}">
                                                        <i class="fas fa-pencil-alt">
                                                        </i>
                                                    </a>
                                                    <button class="btn btn-danger btn-sm"
                                                            data-action="delete"
                                                            data-url="{{ route('posts-categories.destroy', $childrenСhildrenCategory->id) }}"
                                                            tabindex="0"
                                                    >
                                                        <i class="fas fa-trash">
                                                        </i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif

                                @endforeach
                            @endif

                        @endforeach
                        </tbody>
                    </table>
                </div><!-- /.card-body -->
                <div class="card-footer">
                    <nav aria-label="Contacts Page Navigation">
                        <ul class="pagination justify-content-center m-0">
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item"><a class="page-link" href="#">4</a></li>
                            <li class="page-item"><a class="page-link" href="#">5</a></li>
                            <li class="page-item"><a class="page-link" href="#">6</a></li>
                            <li class="page-item"><a class="page-link" href="#">7</a></li>
                            <li class="page-item"><a class="page-link" href="#">8</a></li>
                        </ul>
                    </nav>
                </div><!-- /.card-footer -->
            </div><!-- /.card posts-categories-index -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
@endsection
