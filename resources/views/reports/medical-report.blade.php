<!DOCTYPE html>

<html>

<head>
    <title>Medical Report</title>

    <style>
        body {
            font-family: Arial;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            border: 1px solid #000;
            padding: 8px;
        }
    </style>

</head>

<body>

    <h2>
        Medical Laboratory Report
    </h2>


    <h3>
        Patient:
        {{ $testRequest->patient->name }}
    </h3>

    <p>
        <strong>Payment status:</strong>
        {{ $testRequest->invoice?->status === 'paid' ? 'Paid' : 'Unpaid' }}
    </p>


    <table>

        <tr>
            <th>
                Test
            </th>

            <th>
                Result
            </th>

            <th>
                Unit
            </th>

            <th>
                Reference
            </th>

            <th>
                Flag
            </th>
        </tr>


        @foreach($testRequest->samples as $sample)

            @foreach($sample->testResults as $testResult)

                <tr>

                    <td>
                        {{ 
                            $testResult
                        ->testRequestItem
                        ->test
                        ->name
                        }}
                    </td>


                    <td>
                        {{ $testResult->value }}
                    </td>


                    <td>
                        {{ $testResult->value_unit }}
                    </td>


                    <td>
                        {{ $testResult->reference_range }}
                    </td>


                    <td>
                        {{ ucfirst($testResult->flag) }}
                    </td>

                </tr>

            @endforeach

        @endforeach


    </table>

    <p>
        <strong>Approved by:</strong>
        {{ $testRequest->samples->flatMap->testResults->first()?->approvedBy?->username ?? '—' }}
    </p>


</body>

</html>
