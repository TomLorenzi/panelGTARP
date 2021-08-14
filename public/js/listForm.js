'use strict';

const FolderList = {};

$(function() {
    FolderList.initDatatable();
});

FolderList.initDatatable = function() {
    $('#formList').DataTable({
        stateSave: true,
        dom: "<'row'<'col-sm-8 col-md-4'l><'col-sm-8 col-md-4'p><'col-sm-8 col-md-4'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: {
            url: '//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json'
        },
        processing: true,
        serverSide: true,
        responsive: true,
        bFilter: true,
        aoColumnDefs: [{
            'bSortable': false,
            'aTargets': ['nosort']
        },
            {
                'bSearchable': false,
                'aTargets': ['nosort']
            }
        ],
        lengthMenu: [
            [5, 10, 25, 50],
            [5, 10, 25, 50]
        ],
        pageLength: 10,
        order: [[0, 'desc']],
        columns: [
            {
                data: 'id',
                width: '10px'
            },
            {
                data: 'date',
                width: '100px',
                orderable: false,
                render: function (data, type, row) {
                    return FolderList.formatDate(row.date.date);
                }
            },
            {
                data: 'actions',
                width: '100px',
                orderable: false,
                render: function (data, type, row) {
                    return `<a href="/douane/form/id/${row.id}"><button type="button" class="details btn btn-info">Détails</button></a>`;
                }
            }
        ],
        ajax: {
            url: '/ajax/form',
            method : 'POST',
            cache: false,
            complete: function (data) {
                FolderList.list = data.responseJSON.data;          
            }
        }
    });
}

FolderList.formatDate = function(date) {
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2) 
        month = '0' + month;
    if (day.length < 2) 
        day = '0' + day;

    return [year, month, day].join('-');
}

