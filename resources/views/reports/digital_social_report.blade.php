<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
    <link rel="stylesheet" href="style.css" />
    <style>
        * {
            padding: 0;
            margin: 0;
        }

        .title {
            text-align: center;
        }

        .content {
            margin: 0 5px;
        }

        .heading-content {
            margin-top: 40px;
            display: flex;
        }
        .heading-title {
            font-weight: 700;
        }

        table {
            min-width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
        }

        thead {
            background-color: #f9fafb; /* bg-gray-50 */
        }

        th {
            padding: 8px; /* p-12 */
            text-align: left; /* text-left */
            font-weight: 600; /* font-medium */
            font-size: 10px;
            color: #1a202c; /* text-navy */
            text-transform: uppercase; /* uppercase */
            letter-spacing: 0.05em; /* tracking-wider */
        }

        tbody {
            background-color: #ffffff; /* bg-white */
        }

        td {
            padding: 8px; /* p-12 */
            white-space: nowrap; /* whitespace-nowrap */
            font-size: 15px;
            color: #1a202c; /* text-gray-900 */
        }

        tr {
            border-top: 1px solid #e5e7eb; /* divide-y divide-gray-200 */
        }

        tr:hover {
            background-color: #f3f4f6; /* hover:bg-gray-100 */
        }
        @page {
            margin: 1in; /* 1 inch margin on all sides */
        }
        body {
            margin: 0.5in; /* Adjust as needed */
        }
    </style>
</head>
<body>
<section class="content">
    <h2 class="title">Monthly Tax Report(Details)</h2>
    <h4 class="title">BFIN SASU</h4>
    <table>
        <thead>
        <tr>
            <th>Item</th>
            <th>Value</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Month</td>
            <td>June</td>
        </tr>
        <tr>
            <td>Year</td>
            <td>2024</td>
        </tr>
        <tr>
            <td>Company name</td>
            <td>BFIN SASU</td>
        </tr>
        <tr>
            <td>Address</td>
            <td>8, Rue, Dublin, French</td>
        </tr>
        <tr>
            <td>Employer government no</td>
            <td>805036</td>
        </tr>
        <tr>
            <td>Email</td>
            <td>helen@gmail.com</td>
        </tr>
        <tr>
            <td>BIC/Swift code</td>
            <td>123456</td>
        </tr>
        <tr>
            <td>IBAN/account no</td>
            <td>123456</td>
        </tr>
        <tr>
            <td>Total wages</td>
            <td>24000</td>
        </tr>
        <tr>
            <td>Taxable value</td>
            <td>4000</td>
        </tr>
        <tr>
            <td>Total tax deducted</td>
            <td>4000</td>
        </tr>
        <tr>
            <td>Net Pay Value</td>
            <td>254970</td>
        </tr>
        </tbody>
    </table>
    <table>
        <thead>
        <tr>
            <th>
                Name
            </th>
            <th>
                Tax No
            </th>
            <th>
                Month
            </th>
            <th>
                Year
            </th>
            <th>
                Staff Contribution
            </th>
            <th>
                Company Contribution
            </th>
            <th>
                Combined Contribution
            </th>
        </tr>
        </thead>
        <tbody>
            @foreach($data as $report)
                <tr>
                    <td>
                        {{ $report->employee?->name  }}
                    </td>
                    <td>
                        {{ $report->employee?->user_deatils?->fax }}
                    </td>
                    <td>
                        {{ $report->month  }}
                    </td>
                    <td>
                        {{ date('Y'), strtotime($report->year) }}
                    </td>
                    <td>
                        {{ $report->employee_contribution_value  }}
                    </td>
                    <td>
                        {{ $report->company_contribution_value }}
                    </td>
                    <td>
                        {{ $report->employee_contribution_value + $report->company_contribution_value  }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</section>
</body>
</html>
