@extends('layouts.admin_layout')

@section('title', 'Все категории')

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Все хештеги</h1>
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
            <div class="card hashtags-index" id="hashtags-index">

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

                <ul id="tags" class="tags">
                    @foreach($hashtags as $hashtag)
                        <li data-id="{{ $hashtag->id }}">#{{ $hashtag->title }}<i class="js-delete-image fas fa-times-circle"></i></li>
                    @endforeach
                </ul>

                <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel"></h5>
                            </div>
                            <div class="modal-body"></div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" data-action="delete-request">Удалить</button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-header" style="display: flex; justify-content: end;">
                    <a href="{{ route('hashtags.create') }}" class="btn btn-block btn-outline-info" style="width: 160px;">Добавить хештег</a>
{{--                    <div class="card-tools">--}}
{{--                        <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">--}}
{{--                            <i class="fas fa-minus"></i>--}}
{{--                        </button>--}}
{{--                        <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">--}}
{{--                            <i class="fas fa-times"></i>--}}
{{--                        </button>--}}
{{--                    </div>--}}
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped projects">
                        <thead>
                        <tr>
                            <th style="width: 1%">
                                Id
                            </th>
                            <th style="width: 20%">
                                Название хештега
                            </th>
                            <th style="width: 8%" class="text-center">
                                Status
                            </th>
                            <th style="width: 20%">
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($hashtags as $hashtag)
                        <tr data-id="{{ $hashtag->id }}">
                            <td>
                                {{ $hashtag->id }}
                            </td>
                            <td>
                                {{ $hashtag->title }}
                            </td>
                            <td class="project-state">
                                <span class="badge badge-success">Success</span>
                            </td>
                            <td class="project-actions text-right">
                                <a class="btn btn-primary btn-sm" href="#">
                                    <i class="fas fa-eye">
                                    </i>
                                </a>
                                <a class="btn btn-info btn-sm" href="{{ route('hashtags.edit', $hashtag->id) }}">
                                    <i class="fas fa-pencil-alt">
                                    </i>
                                </a>
                                <button class="btn btn-danger btn-sm"
                                        data-action="delete"
                                        data-url="{{ route('hashtags.destroy', $hashtag->id) }}"
                                        tabindex="0"
                                >
                                    <i class="fas fa-trash">
                                    </i>
                                </button>
                            </td>
                        </tr>
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
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
@endsection
