@extends('layouts.admin_layout')

@section('title', 'Все хештеги')

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

                @include('admin.includes.modal_delete_item')

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
                                        data-page-id="hashtags"
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


                <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-sm-5">
                                <a href="javascript:void(0);" class="btn btn-danger mb-2"><i class="mdi mdi-plus-circle me-2"></i> Add Products</a>
                            </div>
                            <div class="col-sm-7">
                                <div class="text-sm-end">
                                    <button type="button" class="btn btn-success mb-2 me-1"><i class="mdi mdi-cog-outline"></i></button>
                                    <button type="button" class="btn btn-light mb-2 me-1">Import</button>
                                    <button type="button" class="btn btn-light mb-2">Export</button>
                                </div>
                            </div><!-- end col-->
                        </div>

                        <div class="table-responsive">
                            <table class="table table-centered w-100 dt-responsive nowrap" id="products-datatable">
                                <thead class="table-light">
                                <tr>
                                    <th class="all" style="width: 20px;">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="customCheck1">
                                            <label class="form-check-label" for="customCheck1">&nbsp;</label>
                                        </div>
                                    </th>
                                    <th class="all">Product</th>
                                    <th>Category</th>
                                    <th>Added Date</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                    <th style="width: 85px;">Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="customCheck2">
                                            <label class="form-check-label" for="customCheck2">&nbsp;</label>
                                        </div>
                                    </td>
                                    <td>
                                        <img src="assets/images/products/product-1.jpg" alt="contact-img" title="contact-img" class="rounded me-3" height="48" />
                                        <p class="m-0 d-inline-block align-middle font-16">
                                            <a href="apps-ecommerce-products-details.html" class="text-body">Amazing Modern Chair</a>
                                            <br/>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                        </p>
                                    </td>
                                    <td>
                                        Aeron Chairs
                                    </td>
                                    <td>
                                        09/12/2018
                                    </td>
                                    <td>
                                        $148.66
                                    </td>

                                    <td>
                                        254
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                    </td>

                                    <td class="table-action">
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-eye"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-square-edit-outline"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-delete"></i></a>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="customCheck3">
                                            <label class="form-check-label" for="customCheck3">&nbsp;</label>
                                        </div>
                                    </td>
                                    <td>
                                        <img src="assets/images/products/product-4.jpg" alt="contact-img" title="contact-img" class="rounded me-3" height="48" />
                                        <p class="m-0 d-inline-block align-middle font-16">
                                            <a href="apps-ecommerce-products-details.html" class="text-body">Biblio Plastic Armchair</a>
                                            <br/>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star-half"></span>
                                        </p>
                                    </td>
                                    <td>
                                        Wooden Chairs
                                    </td>
                                    <td>
                                        09/08/2018
                                    </td>
                                    <td>
                                        $8.99
                                    </td>

                                    <td>
                                        1,874
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                    </td>
                                    <td class="table-action">
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-eye"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-square-edit-outline"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-delete"></i></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="customCheck4">
                                            <label class="form-check-label" for="customCheck4">&nbsp;</label>
                                        </div>
                                    </td>
                                    <td>
                                        <img src="assets/images/products/product-3.jpg" alt="contact-img" title="contact-img" class="rounded me-3" height="48" />
                                        <p class="m-0 d-inline-block align-middle font-16">
                                            <a href="apps-ecommerce-products-details.html" class="text-body">Branded Wooden Chair</a>
                                            <br/>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star-outline"></span>
                                        </p>
                                    </td>
                                    <td>
                                        Dining Chairs
                                    </td>
                                    <td>
                                        09/05/2018
                                    </td>
                                    <td>
                                        $68.32
                                    </td>

                                    <td>
                                        2,541
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                    </td>

                                    <td class="table-action">
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-eye"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-square-edit-outline"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-delete"></i></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="customCheck5">
                                            <label class="form-check-label" for="customCheck5">&nbsp;</label>
                                        </div>
                                    </td>
                                    <td>
                                        <img src="assets/images/products/product-4.jpg" alt="contact-img" title="contact-img" class="rounded me-3" height="48" />
                                        <p class="m-0 d-inline-block align-middle font-16">
                                            <a href="apps-ecommerce-products-details.html" class="text-body">Designer Awesome Chair</a>
                                            <br/>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star-half"></span>
                                            <span class="text-warning mdi mdi-star-outline"></span>
                                        </p>
                                    </td>
                                    <td>
                                        Baby Chairs
                                    </td>
                                    <td>
                                        08/23/2018
                                    </td>
                                    <td>
                                        $112.00
                                    </td>

                                    <td>
                                        3,540
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                    </td>

                                    <td class="table-action">
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-eye"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-square-edit-outline"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-delete"></i></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="customCheck6">
                                            <label class="form-check-label" for="customCheck6">&nbsp;</label>
                                        </div>
                                    </td>
                                    <td>
                                        <img src="assets/images/products/product-5.jpg" alt="contact-img" title="contact-img" class="rounded me-3" height="48" />
                                        <p class="m-0 d-inline-block align-middle font-16">
                                            <a href="apps-ecommerce-products-details.html" class="text-body">Cardan Armchair</a>
                                            <br/>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                        </p>
                                    </td>
                                    <td>
                                        Plastic Armchair
                                    </td>
                                    <td>
                                        08/02/2018
                                    </td>
                                    <td>
                                        $59.69
                                    </td>

                                    <td>
                                        26
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                    </td>

                                    <td class="table-action">
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-eye"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-square-edit-outline"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-delete"></i></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="customCheck7">
                                            <label class="form-check-label" for="customCheck7">&nbsp;</label>
                                        </div>
                                    </td>
                                    <td>
                                        <img src="assets/images/products/product-3.jpg" alt="contact-img" title="contact-img" class="rounded me-3" height="48" />
                                        <p class="m-0 d-inline-block align-middle font-16">
                                            <a href="apps-ecommerce-products-details.html" class="text-body">Bootecos Plastic Armchair</a>
                                            <br/>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star-half"></span>
                                        </p>
                                    </td>
                                    <td>
                                        Wing Chairs
                                    </td>
                                    <td>
                                        07/15/2018
                                    </td>
                                    <td>
                                        $148.66
                                    </td>

                                    <td>
                                        485
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">Deactive</span>
                                    </td>

                                    <td class="table-action">
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-eye"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-square-edit-outline"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-delete"></i></a>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="customCheck8">
                                            <label class="form-check-label" for="customCheck8">&nbsp;</label>
                                        </div>
                                    </td>
                                    <td>
                                        <img src="assets/images/products/product-6.jpg" alt="contact-img" title="contact-img" class="rounded me-3" height="48" />
                                        <p class="m-0 d-inline-block align-middle font-16">
                                            <a href="apps-ecommerce-products-details.html" class="text-body">Adirondack Chair</a>
                                            <br/>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                        </p>
                                    </td>
                                    <td>
                                        Aeron Chairs
                                    </td>
                                    <td>
                                        07/07/2018
                                    </td>
                                    <td>
                                        $65.94
                                    </td>

                                    <td>
                                        652
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                    </td>

                                    <td class="table-action">
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-eye"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-square-edit-outline"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-delete"></i></a>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="customCheck9">
                                            <label class="form-check-label" for="customCheck9">&nbsp;</label>
                                        </div>
                                    </td>
                                    <td>
                                        <img src="assets/images/products/product-2.jpg" alt="contact-img" title="contact-img" class="rounded me-3" height="48" />
                                        <p class="m-0 d-inline-block align-middle font-16">
                                            <a href="apps-ecommerce-products-details.html" class="text-body">Bean Bag Chair</a>
                                            <br/>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                        </p>
                                    </td>
                                    <td>
                                        Wooden Chairs
                                    </td>
                                    <td>
                                        06/30/2018
                                    </td>
                                    <td>
                                        $99
                                    </td>

                                    <td>
                                        1,021
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">Deactive</span>
                                    </td>
                                    <td class="table-action">
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-eye"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-square-edit-outline"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-delete"></i></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="customCheck10">
                                            <label class="form-check-label" for="customCheck10">&nbsp;</label>
                                        </div>
                                    </td>
                                    <td>
                                        <img src="assets/images/products/product-3.jpg" alt="contact-img" title="contact-img" class="rounded me-3" height="48" />
                                        <p class="m-0 d-inline-block align-middle font-16">
                                            <a href="apps-ecommerce-products-details.html" class="text-body">The butterfly chair</a>
                                            <br/>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star-half"></span>
                                        </p>
                                    </td>
                                    <td>
                                        Dining Chairs
                                    </td>
                                    <td>
                                        06/19/2018
                                    </td>
                                    <td>
                                        $58
                                    </td>

                                    <td>
                                        874
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                    </td>

                                    <td class="table-action">
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-eye"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-square-edit-outline"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-delete"></i></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="customCheck11">
                                            <label class="form-check-label" for="customCheck11">&nbsp;</label>
                                        </div>
                                    </td>
                                    <td>
                                        <img src="assets/images/products/product-4.jpg" alt="contact-img" title="contact-img" class="rounded me-3" height="48" />
                                        <p class="m-0 d-inline-block align-middle font-16">
                                            <a href="apps-ecommerce-products-details.html" class="text-body">Eames Lounge Chair</a>
                                            <br/>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star-half"></span>
                                        </p>
                                    </td>
                                    <td>
                                        Baby Chairs
                                    </td>
                                    <td>
                                        05/06/2018
                                    </td>
                                    <td>
                                        $39.5
                                    </td>

                                    <td>
                                        1,254
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                    </td>

                                    <td class="table-action">
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-eye"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-square-edit-outline"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-delete"></i></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="customCheck12">
                                            <label class="form-check-label" for="customCheck12">&nbsp;</label>
                                        </div>
                                    </td>
                                    <td>
                                        <img src="assets/images/products/product-5.jpg" alt="contact-img" title="contact-img" class="rounded me-3" height="48" />
                                        <p class="m-0 d-inline-block align-middle font-16">
                                            <a href="apps-ecommerce-products-details.html" class="text-body">Farthingale Chair</a>
                                            <br/>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star-half"></span>
                                        </p>
                                    </td>
                                    <td>
                                        Plastic Armchair
                                    </td>
                                    <td>
                                        04/09/2018
                                    </td>
                                    <td>
                                        $78.66
                                    </td>

                                    <td>
                                        524
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">Deactive</span>
                                    </td>

                                    <td class="table-action">
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-eye"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-square-edit-outline"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-delete"></i></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="customCheck13">
                                            <label class="form-check-label" for="customCheck13">&nbsp;</label>
                                        </div>
                                    </td>
                                    <td>
                                        <img src="assets/images/products/product-6.jpg" alt="contact-img" title="contact-img" class="rounded me-3" height="48" />
                                        <p class="m-0 d-inline-block align-middle font-16">
                                            <a href="apps-ecommerce-products-details.html" class="text-body">Unpowered aircraft</a>
                                            <br/>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star"></span>
                                            <span class="text-warning mdi mdi-star-half"></span>
                                        </p>
                                    </td>
                                    <td>
                                        Wing Chairs
                                    </td>
                                    <td>
                                        03/24/2018
                                    </td>
                                    <td>
                                        $49
                                    </td>

                                    <td>
                                        204
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">Deactive</span>
                                    </td>

                                    <td class="table-action">
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-eye"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-square-edit-outline"></i></a>
                                        <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-delete"></i></a>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div> <!-- end card-body-->



            </div><!-- /.card -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
@endsection
