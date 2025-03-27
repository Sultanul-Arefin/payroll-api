<?xml version="1.0" encoding="UTF-8"?>
<MonthlyTaxReport>
    <Company>
        <Name>{{ $others['company_name'] }}</Name>
        <Address>{{ $others['company_address'] }}</Address>
        <GovernmentNo>{{ $others['company_government_no'] }}</GovernmentNo>
        <Email>{{ $others['company_email'] }}</Email>
        <BankBICSwift>{{ $others['company_bank_bic_or_swift_code'] }}</BankBICSwift>
        <BankIBAN>{{ $others['company_bank_iban_or_account_no'] }}</BankIBAN>
    </Company>

    <ReportSummary>
        <Month>{{ $others['month'] }}</Month>
        <Year>{{ $others['year'] }}</Year>
        <TotalWages>{{ $total['total_wages'] }}</TotalWages>
        <TaxableValue>{{ $total['total_taxable_value'] }}</TaxableValue>
        <TotalTaxDeducted>{{ $total['total_tax_deducted'] }}</TotalTaxDeducted>
        <NetPay>{{ $total['total_net_pay'] }}</NetPay>
    </ReportSummary>

    <Employees>
        @foreach($data as $report)
        <Employee>
            <Name>{{ $report->employee?->name }}</Name>
            <TaxNo>{{ $report->employee?->user_deatils?->fax }}</TaxNo>
            <Month>{{ $report->month }}</Month>
            <Year>{{ date('Y', strtotime($report->year)) }}</Year>
            <StaffContribution>{{ $report->employee_contribution_value }}</StaffContribution>
            <CompanyContribution>{{ $report->company_contribution_value }}</CompanyContribution>
            <CombinedContribution>{{ $report->employee_contribution_value + $report->company_contribution_value }}</CombinedContribution>
        </Employee>
        @endforeach
    </Employees>
</MonthlyTaxReport>
