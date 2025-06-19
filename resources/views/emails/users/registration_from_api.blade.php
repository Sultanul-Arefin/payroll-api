<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Invoice</title>
    <link rel="stylesheet" href="style.css" />
    <style>
        /* Resetting default margins and paddings */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* General Body Styles */
        body {
            font-family: Arial, sans-serif;
            color: black; /* Default text color */
            background-color: #f4f4f4; /* Light background color for the body */
        }

        .container{
            width: 60%;
            margin: auto;
        }

        /* Invoice Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .invoice-header h5 {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333; /* Black text color */
        }

        .invoice-header img {
            max-height: 50px;
            max-width: auto;
        }

        /* Address Container (To & From) */
        .address-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .address-container h5 {
            font-size: 1rem;
            font-weight: bold;
            color: #333; /* Black text color */
        }

        .address-container p {
            font-size: 0.875rem;
            color: #333; /* Black text color */
        }

        /* Table Styles */
        .custom-table {
            width: 100%;
            background-color: white;
            border-collapse: collapse; /* Ensures that borders are collapsed into single lines */
            margin-top: 20px;
        }

        .table-header {
            background-color: #3b82f6; /* Blue background for the header */
            color: white; /* White text in the header */
            text-align: left;
            height: 50px;
        }

        .table-header th {
            padding: 12px;
            font-size: 1rem;
            font-weight: bold;
        }

        /* Table Cell Styles */
        .table-cell {
            padding: 12px;
            font-size: 0.875rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0; /* Light border between rows */
        }

        .table-cell[colspan="2"] {
            text-align: left;
        }

        /* Label Styles for subtotal, tax, and total rows */
        .subtotal-label, .tax-label, .total-label {
            font-weight: bold;
            color: #333; /* Black text for subtotal, taxes, and total */
        }

        /* Additional Styling for the Tax and Total Rows */
        .tax-label {
            font-size: 0.875rem;
        }

        .total-label {
            font-size: 1rem;
            font-weight: bold;
        }

        /* Adding Border between columns */
        .table-cell + .table-cell {
            border-left: 1px solid #e0e0e0; /* Border between columns */
        }

        /* Optional: Improve spacing for table cells with large text or headings */
        .table-cell, .table-header th {
            padding-left: 16px;
            padding-right: 16px;
        }

        /* Hover effects (Optional for rows) */
        .table-row:hover {
            background-color: #f9fafb; /* Light background on hover */
        }

        /* Invoice Footer */
        .invoice-footer {
            margin-top: 40px;
            border-top: 2px solid #e0e0e0; /* Light border at the top */
        }

        .invoice-footer h5 {
            font-size: 1.25rem;
            margin-top: 10px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .invoice-footer p {
            font-size: 1rem;
            margin-top: 0;
        }
    </style>
  </head>
  <body>
    <section class="container">
      <div class="invoice-header">
        <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAjoAAAG2CAMAAABBDNzMAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAGNQTFRFzCsxLz532WBl8srLY26Z5ZWYl567y8/d/PLyzzg+33t/1lNY9dfY7K+x0kZL+eTl6aKl3G5y4oiL77y+sbbMvsLU8vP2fYeqPEqA5efuSVaIcHui2NvlipOzVmKRpKrD////ZWUg0QAAACF0Uk5T//////////////////////////////////////////8An8HQIQAACZNJREFUeNrs3dlimkAAQFELYnHBNfvS+v9fWUFNQEXFKCH03Ie0TeLSeooDzJDOUrqojn8CoSN0hI7QkdAROkJH6AgdCR2hI3SEjtCR0BE6QkfoSOgIHaEjdISOhI7QETpCR+hI6AgdoSN0JHSEjtAROkJHQkfoCJ2blkQ3KSSm9XSCzk2KiEEHHXTQQQcddNBBBx100EEHHXTQQQcddNBBBx100EEHHXTQQQcdoYMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuigg47QQQcddNBBBx100EEHHXTQQQcddNBBBx100EEHHXTQQQcddNBBBx100EEHHXTQQQcddNBBBx100EEHHXTQQQcddNBBBx100EEHHXTQQQcddNBBBx100EEHHXTQQQcddNBBBx100EEHHXTQQQcddNBBBx100EEHHXTQQQcddNBBBx2hgw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDjtBBBx100EEHHXTQQQedxtCZBzcpQaf1dG7/wqKDDjrooIMOOkIHHXTQQQcddNBBBx100EEHHXTQQQcddNBBBx100EEHHXTQQQcdoYMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjroEIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjroLEfhTZoQ03o6QgcddNBBBx2hI3TQQQcddL6ZztvvJodOg+l0fzU5dNBBBx100EEHHXTQQQedhtDpB/MoDEfb+0qnV0TRIBgX6Nz1zmjzQr48rn7/+oROu+kMp6PSe608m2YN536r7Qmd1tLpR0Uc6eSr+AtPI30VHx8+/vjwB5120ulHGyaTJAqK9zAMgihahDlRp4o3dB7TX+Nwmm2xarODTq10gvUWZzQfl3/PYP13OqNwTecl3ebMs8/MVpre0Gkhnel6g3L8lkFVOnerj7Ptpmtl5xGdttHpZy/15NTtqtJ5Wn1I8vv29+i0jE4/261K+p0r03ktPJfxdqcLnfbQWaQ3GpwxHqpIp1f87tWfXtBpFZ3ooJx+kNW/Kp0uOm2iM14WhiSpmkGSOzIYh5fSeV99GBfoPKHTJjpJOkLObVzGyd7f4jCdk8d1/hS2ZrPl8sFYp010xjtvV4P9A8gldE4fTX5ebbM+UK42ZHfotIlOOtKJi9ugq9FJDyaP1nb6i9pGyejURCcd1Ux3DhifT+f5+Jnzt9TldBbMpum2rOdocpvo9Je5I76dflyRzu/jL+LTW+5uanq7QqcmOhmHj/HIfHldOr+etnMulg/vJl20i05U3Gm6Np1fv7p3z+n3vdc41wud2uiMTox8v0Tn7F4ee/fpArz7L08pRKc2OmFhR/2KdB53lmVuJuwUP/k3c/P3Of+I94/o/Cw6wZXp9HbuaHMeoriyd3UX3f21vg89dH7SWGf8HXTenu42vwsX0artlPrnLjqNpjMv7GHdgE60rUAn3HwyHZdn05fjaZA7h7YervfQafzO+aww/eK6dA6cOP+7AlVc2zXZPXE/XnzhSBA69RxNTm+QHD2YfHU6vc9HnGV3OT0wyyydzHyhHXTqoZP+9/48RzmpiU6Yu8v48PMdp2Oed3SaSyfb0ERH97FuSyceHpv3+gedxtLJNjTx+Nhb1k3plMpZbXcuXICDTk10MizhhfN1LqLz+vl4/aNPNh0JPaLTWDqdUWGk3BmHFei8dcs6QqebozotTGqNw2i8M1H1GZ3m0hnGRTudYRKfS6e8M+lsBujR5yMuxsUpjK/oNJbOZnxTWIc1HETJx0zjG9CZFPEWrq+RH/2El6z7Q6c2Ohs7VVd/foXOcn+zd9/rdl+zs6A5O+kze0KnuXS2+1XJuAqdoKxRNTrpjtTy/iU3tB4Vjli+otNgOts5XvHgGqs/w2p0dg79pau3Bvn7+otOc+lsxhrh4DoLh6vRSU/AFk50/s4PhaILppOhUxudIBtrjE7e7DZ04l0c6ZeHucd8QKepdNYDneh6lyuoRGew3FuK/px7NsNl9etjoFMTnWE2yNm9zTCYf8y0uSmdZP9kw33+6Pay+kUO0KmHTrZ/UzyRtHsd05vSmexP6erlBzvoNJZOuCtnMDnzRMR1ds4PnKZ6PXwzdJpFJxvozPcuR3nrExFHaRS+HqLTTDrZSuGwc/Ga85vRGaPTcDoZlXHn4gmmD6U/Cu+LdAJ0Gk4nHZYsOrdch4VOO+lkC69mnaMzk9FBp2wV1om3K3TQKVkOEXaOr8JCB52SoU6UP5uEDjrnr9+LTqw4RwedEjqzE/tX6KBTQidABx1bHXS+Z6zTRwedSouGo1NHBNFB5/CMi/DoldrRQeeMq/wH6KBzbrPCOLnkssnooHOg9AByUpxsig4655QNb/on3rLQQadkfcx0d/k3OuicuXohN6u9n6CDzvmrsEaF66MkMTronNF0udz5qbGrHa9pmOMzQQed0uuE7trJdrfWi6qOX18Hnf+Yzv714Kpcmgmd/5nOxs5oiA46VS+SstkjP3S5fXTQOefSTHEyRAedavWnm5uOpgE66FTb8Hye+gyn82Bv89MPgnmCDjoHtym7x5E/rps8KX+oi34KX/fwpwt0jn0dnYbRSS+2v6j6UOig87HtiRZlG5kJOuicvIpBMIvyzbOjytHlL2a9ofN9dMpno6KDDjrooIMOOuiggw466PxndISO0JHQETpCR+hI6Ohn0rnrNbncE43DJjf5D+n8mGO9YafJReiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjrooIMOOuiggw466KCDDjro7PbebXLoNJiOWhA6QkfoCB2hI6EjdISO0BE6EjpCR+gIHaEjoSN0hI7QkdAROkJH6AgdCR2hI3SEjtCR0BE6QkfoSOgIHaEjdISOhI7QETpCR+hI6AgdoaO29U+AAQCDQNHb2dOLFwAAAABJRU5ErkJggg==" alt="Logo" />
        {{-- <h5>Invoice ID: {{ $data['invoice_id'] }}</h5> --}}
      </div>
      <div class="address-container">
        <div>
          <h5>To</h5>
          <p>{{ $name }}</p>
        </div>
        <div>
          <h5>From</h5>
          <p>Payroll Omada Clasico</p>
        </div>
      </div>
      <div class="invoice-footer">
        <p>Thank You For Registering With Us!</p>
        <p>This is your login credential</p>
        <p>URL: <a href="https://payroll.omada-clasico.com/">https://payroll.omada-clasico.com/</a></p>
        <p>Email: {{ $email }}</p>
        <p>Password: password </p>
      </div>
      <div class="invoice-footer">
        <h5>Thank you</h5>pa
        <p>Payroll Omada Clasico</p>
      </div>
    </section>
  </body>
</html>
