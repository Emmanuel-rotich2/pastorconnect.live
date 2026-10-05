<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/email.php';

member_required();

$member = current_member($pdo);

$date = $_GET['date'] ?? next_wednesday();

if (!is_wednesday($date) || $date < date('Y-m-d')) {
    $date = next_wednesday();
}

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| Make sure Wednesday slots exist
|--------------------------------------------------------------------------
*/
ensure_wednesday_slots($pdo, $date);


/*
|--------------------------------------------------------------------------
| Process appointment booking
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        verify_csrf();

        $date = $_POST['appointment_date'] ?? '';
        $sid = (int)($_POST['slot_id'] ?? 0);

        $purpose = trim($_POST['purpose'] ?? '');
        $otherPurpose = trim($_POST['other_purpose'] ?? '');

        $notes = trim($_POST['notes'] ?? '');


        /*
        |--------------------------------------------------------------------------
        | Validate office status
        |--------------------------------------------------------------------------
        */
        if (setting($pdo, 'office_status', 'available') !== 'available') {

            throw new RuntimeException(
                "The pastor's office is currently closed. Please check again later."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Validate appointment date
        |--------------------------------------------------------------------------
        */
        if (!is_wednesday($date) || $date < date('Y-m-d')) {

            throw new RuntimeException(
                'Please select a valid future Wednesday.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Validate slot
        |--------------------------------------------------------------------------
        */
        if ($sid <= 0) {

            throw new RuntimeException(
                'Please select an appointment slot.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Validate purpose
        |--------------------------------------------------------------------------
        */
        if ($purpose === '') {

            throw new RuntimeException(
                'Please select the purpose of seeing the Pastor.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | If Other is selected, require additional explanation
        |--------------------------------------------------------------------------
        */
        if ($purpose === 'Other') {

            if ($otherPurpose === '') {

                throw new RuntimeException(
                    'Please specify the purpose of your appointment.'
                );
            }

            /*
             * Store the actual explanation together with "Other".
             */
            $purpose = 'Other: ' . $otherPurpose;
        }


        /*
        |--------------------------------------------------------------------------
        | Purpose length
        |--------------------------------------------------------------------------
        */
        if (mb_strlen($purpose) > 120) {

            throw new RuntimeException(
                'The appointment purpose must not exceed 120 characters.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Ensure slots exist
        |--------------------------------------------------------------------------
        */
        ensure_wednesday_slots($pdo, $date);


        /*
        |--------------------------------------------------------------------------
        | Start transaction
        |--------------------------------------------------------------------------
        */
        $pdo->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | Get appointment schedule
        |--------------------------------------------------------------------------
        */
        [$open, $close, $slotMinutes] = appointment_schedule($pdo);


        /*
        |--------------------------------------------------------------------------
        | Lock and verify selected slot
        |--------------------------------------------------------------------------
        */
        $q = $pdo->prepare(
            "SELECT s.*
             FROM appointment_slots s
             WHERE s.id = ?
               AND s.appointment_date = ?
               AND s.availability = 'available'
               AND s.start_time >= ?
               AND s.end_time <= ?
             FOR UPDATE"
        );

        $q->execute([
            $sid,
            $date,
            $open . ':00',
            $close . ':00'
        ]);

        $slot = $q->fetch(PDO::FETCH_ASSOC);


        if (!$slot) {

            throw new RuntimeException(
                'That slot is closed or no longer available.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Check whether slot has already been booked
        |--------------------------------------------------------------------------
        */
        $q = $pdo->prepare(
            "SELECT id
             FROM appointments
             WHERE slot_id = ?
               AND status IN ('pending', 'confirmed')
             FOR UPDATE"
        );

        $q->execute([$sid]);


        if ($q->fetch()) {

            throw new RuntimeException(
                'That slot has just been booked by another member.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent multiple upcoming appointments
        |--------------------------------------------------------------------------
        */
        $q = $pdo->prepare(
            "SELECT a.id
             FROM appointments a
             JOIN appointment_slots s
               ON s.id = a.slot_id
             WHERE a.member_id = ?
               AND a.status IN ('pending', 'confirmed')
               AND s.appointment_date >= CURDATE()
             LIMIT 1
             FOR UPDATE"
        );

        $q->execute([
            $member['id']
        ]);


        if ($q->fetch()) {

            throw new RuntimeException(
                'You already have an upcoming appointment.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Generate appointment reference
        |--------------------------------------------------------------------------
        */
        $ref = appointment_no();


        /*
        |--------------------------------------------------------------------------
        | Insert appointment
        |--------------------------------------------------------------------------
        */
        $q = $pdo->prepare(
            "INSERT INTO appointments
                (
                    appointment_no,
                    member_id,
                    slot_id,
                    purpose,
                    notes
                )
             VALUES
                (?, ?, ?, ?, ?)"
        );

        $q->execute([
            $ref,
            $member['id'],
            $sid,
            $purpose,
            $notes
        ]);


        /*
        |--------------------------------------------------------------------------
        | Get appointment ID
        |--------------------------------------------------------------------------
        */
        $appointmentId = (int)$pdo->lastInsertId();


        /*
        |--------------------------------------------------------------------------
        | Activity log
        |--------------------------------------------------------------------------
        */
        log_activity(
            $pdo,
            $member['id'],
            null,
            'appointment_booked',
            "Appointment $ref booked"
        );


        /*
        |--------------------------------------------------------------------------
        | Commit appointment FIRST
        |--------------------------------------------------------------------------
        |
        | The appointment is saved before attempting email.
        | Therefore, an email failure cannot destroy the booking.
        |
        */
        $pdo->commit();

        $success = $ref;


        /*
        |--------------------------------------------------------------------------
        | Notify pastor through portal
        |--------------------------------------------------------------------------
        */
        try {

            $slotLabel =
                fmt_date($date) .
                ' • ' .
                fmt_time($slot['start_time']) .
                ' – ' .
                fmt_time($slot['end_time']);


            notify_staff(
                $pdo,
                'New appointment request',
                $member['full_name'] .
                ' has requested an appointment for ' .
                $slotLabel .
                '. Reference: ' .
                $ref .
                '. Log in to the Pastor Portal to review and confirm it.',
                'info'
            );


        } catch (Throwable $notificationError) {

            if (function_exists('email_log')) {

                email_log(
                    'PORTAL NOTIFICATION ERROR: ' .
                    $notificationError->getMessage()
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Send SMS notification to active pastor(s)
        |--------------------------------------------------------------------------
        */
        try {
            $pastorSms = $pdo->query("SELECT full_name, phone FROM users WHERE role='pastor' AND status='active' AND phone IS NOT NULL AND phone <> ''")->fetchAll();
            $smsMessage = 'Praise the Lord. FGCK Joyland: ' . $member['full_name'] . ' has booked a pastor appointment for ' . $slotLabel . '. Ref: ' . $ref . '. Please check the Pastor Portal.';
            foreach ($pastorSms as $pastorRow) {
                send_sms((string)$pastorRow['phone'], $smsMessage);
            }
        } catch (Throwable $smsError) {
            sms_log('BOOKING SMS ERROR: ' . $smsError->getMessage());
        }


        /*
        |--------------------------------------------------------------------------
        | Send email notification to pastor
        |--------------------------------------------------------------------------
        */
        try {

            if (function_exists('email_log')) {

                email_log(
                    'BOOKING EMAIL: Starting pastor notification. ' .
                    'Appointment ID=' .
                    $appointmentId .
                    ', Reference=' .
                    $ref .
                    ', Member=' .
                    $member['full_name']
                );
            }


            /*
             * Send email to active pastor(s).
             */
            email_pastors_about_booking(
                $pdo,
                $appointmentId
            );


            if (function_exists('email_log')) {

                email_log(
                    'BOOKING EMAIL: email_pastors_about_booking() completed. ' .
                    'Appointment ID=' .
                    $appointmentId .
                    ', Reference=' .
                    $ref
                );
            }


        } catch (Throwable $emailError) {

            /*
             * Appointment has already been saved.
             * Do not show the member a database failure
             * simply because email failed.
             */

            if (function_exists('email_log')) {

                email_log(
                    'BOOKING EMAIL ERROR: ' .
                    $emailError->getMessage() .
                    ' | File: ' .
                    $emailError->getFile() .
                    ' | Line: ' .
                    $emailError->getLine()
                );
            }
        }


    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {

            $pdo->rollBack();
        }

        $error = $e->getMessage();
    }
}


/*
|--------------------------------------------------------------------------
| Refresh slots
|--------------------------------------------------------------------------
*/
ensure_wednesday_slots($pdo, $date);

[$open, $close, $slotMinutes] = appointment_schedule($pdo);

$slots = schedule_slots($pdo, $date);


$officeStatus = setting(
    $pdo,
    'office_status',
    'available'
);


$bookingNotice = setting(
    $pdo,
    'booking_notice',
    'Please arrive a few minutes before your appointment.'
);


$page_title = 'Book Appointment';

require __DIR__ . '/../includes/header.php';

?>

<div>

    <?php require __DIR__ . '/../includes/sidebar.php'; ?>


    <main class="main">


        <!-- TOP BAR -->
        <header class="topbar">

            <div class="d-flex align-items-center gap-3">

                <button
                    class="menu-btn"
                    data-sidebar-toggle
                    type="button"
                    aria-label="Open menu"
                >

                    <i class="bi bi-list"></i>

                </button>


                <div>

                    <h1>Book Appointment</h1>

                    <p>
                        Choose an available Wednesday slot.
                    </p>

                </div>

            </div>

        </header>


        <!-- CONTENT -->
        <div class="content">


            <!-- OFFICE STATUS -->
            <div
                id="officeStatusBox"
                class="alert
                <?= $officeStatus === 'available'
                    ? 'alert-success'
                    : 'alert-danger'
                ?>
                d-flex align-items-center gap-2 py-3"
            >

                <i
                    class="bi
                    <?= $officeStatus === 'available'
                        ? 'bi-check-circle'
                        : 'bi-door-closed'
                    ?>
                    fs-5"
                ></i>


                <div>

                    <strong id="officeStatusTitle">

                        <?= $officeStatus === 'available'
                            ? "Pastor's Office is Open"
                            : "Pastor's Office is Closed"
                        ?>

                    </strong>


                    <div
                        class="small"
                        id="officeStatusText"
                    >

                        <?= $officeStatus === 'available'
                            ? e($bookingNotice)
                            : 'Appointments are temporarily closed. Please check again later.'
                        ?>

                    </div>

                </div>

            </div>


            <!-- SUCCESS -->
            <?php if ($success): ?>

                <div class="cardx p-5 text-center">

                    <div class="brand-mark mx-auto mb-3">

                        <i class="bi bi-check2"></i>

                    </div>


                    <h2 class="section-title fs-4">

                        Appointment request submitted

                    </h2>


                    <p class="text-muted small">

                        Your reference number

                    </p>


                    <h3>

                        <?= e($success) ?>

                    </h3>


                    <p class="small text-muted">

                        Your appointment request has been
                        successfully submitted.

                        The pastor can now review and
                        confirm your appointment.

                    </p>


                    <a
                        class="btn btn-primary"
                        href="/member/appointments"
                    >

                        <i class="bi bi-calendar-check me-1"></i>

                        View Appointment

                    </a>

                </div>


            <?php else: ?>


                <!-- ERROR -->
                <?php if ($error): ?>

                    <div
                        class="alert alert-danger"
                        role="alert"
                    >

                        <i
                            class="bi bi-exclamation-triangle me-2"
                        ></i>

                        <?= e($error) ?>

                    </div>

                <?php endif; ?>


                <!-- DATE SELECTOR -->
                <div class="cardx p-4 mb-4">

                    <div class="row align-items-end g-3">


                        <div class="col-md-5">

                            <label
                                class="form-label"
                                for="datePicker"
                            >

                                Appointment Wednesday

                            </label>


                            <input
                                type="date"
                                class="form-control"
                                id="datePicker"
                                value="<?= e($date) ?>"
                                min="<?= e(date('Y-m-d')) ?>"
                            >

                        </div>


                        <div class="col-md-7">

                            <div
                                class="alert alert-info mb-0"
                                id="durationNotice"
                            >

                                <i
                                    class="bi bi-clock me-1"
                                ></i>

                                Each appointment is

                                <strong>
                                    <?= (int)$slotMinutes ?> minutes
                                </strong>.

                                The Pastor controls the opening
                                time; all appointments end by

                                <strong>
                                    3:00 PM
                                </strong>.

                            </div>

                        </div>

                    </div>

                </div>


                <!-- AVAILABLE SLOTS -->
                <div class="cardx p-4">


                    <div
                        class="d-flex justify-content-between
                        align-items-center mb-3"
                    >

                        <h3 class="section-title mb-0">

                            Available time slots

                        </h3>


                        <span class="small text-muted">

                            <?= e(fd($date)) ?>

                        </span>

                    </div>


                    <div
                        id="slotsGrid"
                        class="row g-3"
                    >


                        <?php if (!$slots): ?>

                            <div class="col-12">

                                <div class="alert alert-warning">

                                    <i
                                        class="bi bi-calendar-x me-2"
                                    ></i>

                                    No appointment slots are
                                    currently available for
                                    this Wednesday.

                                </div>

                            </div>

                        <?php endif; ?>


                        <?php foreach ($slots as $s): ?>

                            <div class="col-sm-6 col-lg-4">


                                <div
                                    class="slot
                                    <?= $s['booked']
                                        ? 'booked'
                                        : (
                                            $s['availability'] !== 'available'
                                                ? 'closed'
                                                : ''
                                        )
                                    ?>"
                                >


                                    <!-- STATUS -->
                                    <?php if ($officeStatus !== 'available'): ?>

                                        <span
                                            class="badge text-bg-danger float-end"
                                        >

                                            Closed

                                        </span>


                                    <?php elseif ($s['booked']): ?>

                                        <span
                                            class="badge text-bg-secondary float-end"
                                        >

                                            Booked

                                        </span>


                                    <?php elseif (
                                        $s['availability'] !== 'available'
                                    ): ?>

                                        <span
                                            class="badge text-bg-danger float-end"
                                        >

                                            Closed

                                        </span>


                                    <?php else: ?>

                                        <span
                                            class="badge-soft float-end"
                                        >

                                            Available

                                        </span>

                                    <?php endif; ?>


                                    <!-- TIME -->
                                    <div class="slot-time">

                                        <?= e(
                                            ft($s['start_time'])
                                        ) ?>

                                    </div>


                                    <!-- DURATION -->
                                    <div class="slot-duration">

                                        <?= e(
                                            ft($s['start_time'])
                                        ) ?>

                                        –

                                        <?= e(
                                            ft($s['end_time'])
                                        ) ?>

                                    </div>


                                    <!-- SELECT -->
                                    <?php if (
                                        !$s['booked'] &&
                                        $s['availability'] === 'available' &&
                                        $officeStatus === 'available'
                                    ): ?>

                                        <button
                                            type="button"
                                            class="btn btn-primary btn-sm mt-3 w-100"
                                            data-slot="<?= (int)$s['id'] ?>"
                                            data-time="<?= e(
                                                ft($s['start_time'])
                                            ) ?>"
                                            data-duration="<?= e(
                                                ft($s['start_time']) .
                                                ' – ' .
                                                ft($s['end_time'])
                                            ) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#bookingModal"
                                        >

                                            <i
                                                class="bi bi-calendar-plus me-1"
                                            ></i>

                                            Select Slot

                                        </button>

                                    <?php endif; ?>


                                </div>

                            </div>

                        <?php endforeach; ?>


                    </div>

                </div>


            <?php endif; ?>


        </div>

    </main>

</div>


<!-- BOOKING MODAL -->
<div
    class="modal fade"
    id="bookingModal"
    tabindex="-1"
    aria-hidden="true"
>


    <div class="modal-dialog modal-dialog-centered">


        <div class="modal-content border-0 rounded-4">


            <form
                method="post"
                id="bookingForm"
            >


                <input
                    type="hidden"
                    name="csrf"
                    value="<?= e(csrf_token()) ?>"
                >


                <input
                    type="hidden"
                    name="appointment_date"
                    value="<?= e($date) ?>"
                >


                <input
                    type="hidden"
                    name="slot_id"
                    id="slotId"
                >


                <!-- HEADER -->
                <div class="modal-header">

                    <h5 class="modal-title">

                        <i
                            class="bi bi-calendar-check me-1"
                        ></i>

                        Confirm appointment

                    </h5>


                    <button
                        class="btn-close"
                        data-bs-dismiss="modal"
                        type="button"
                        aria-label="Close"
                    ></button>

                </div>


                <!-- BODY -->
                <div class="modal-body">


                    <div class="alert alert-light">

                        <strong id="selectedTime">

                            Selected appointment

                        </strong>


                        <br>


                        <small id="selectedDuration">

                            Appointment time controlled by
                            the Pastor

                        </small>

                    </div>


                    <!-- PURPOSE -->
                    <label
                        class="form-label"
                        for="purpose"
                    >

                        Purpose of seeing the Pastor

                    </label>


                    <select
                        class="form-select mb-3"
                        id="purpose"
                        name="purpose"
                        required
                    >

                        <option
                            value=""
                            selected
                            disabled
                        >

                            Select purpose of appointment

                        </option>


                        <option value="Counselling">
                            Counselling
                        </option>


                        <option value="Prayer">
                            Prayer
                        </option>


                        <option value="Spiritual Guidance">
                            Spiritual Guidance
                        </option>


                        <option value="Family/Marriage Matter">
                            Family / Marriage Matter
                        </option>


                        <option value="Personal Matter">
                            Personal Matter
                        </option>


                        <option value="Youth/Young Adults">
                            Youth / Young Adults
                        </option>


                        <option value="Baptism">
                            Baptism
                        </option>


                        <option value="Wedding/Marriage">
                            Wedding / Marriage
                        </option>


                        <option value="Bereavement/Condolence">
                            Bereavement / Condolence
                        </option>


                        <option value="Church Membership">
                            Church Membership
                        </option>


                        <option value="Ministry/Church Service">
                            Ministry / Church Service
                        </option>


                        <option value="Financial/Support Request">
                            Financial / Support Request
                        </option>


                        <option value="Other">
                            Other
                        </option>

                    </select>


                    <!-- OTHER PURPOSE -->
                    <div
                        id="otherPurposeContainer"
                        class="mb-3"
                        style="display:none;"
                    >

                        <label
                            class="form-label"
                            for="otherPurpose"
                        >

                            Please specify

                        </label>


                        <input
                            type="text"
                            class="form-control"
                            id="otherPurpose"
                            name="other_purpose"
                            maxlength="110"
                            placeholder="Enter the reason for seeing the Pastor"
                        >

                    </div>


                    <!-- NOTES -->
                    <label
                        class="form-label"
                        for="notes"
                    >

                        Additional notes

                        <span class="text-muted">
                            (optional)
                        </span>

                    </label>


                    <textarea
                        class="form-control"
                        id="notes"
                        name="notes"
                        rows="4"
                        maxlength="1000"
                        placeholder="Add any additional information..."
                    ></textarea>


                </div>


                <!-- FOOTER -->
                <div class="modal-footer">


                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >

                        Back

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="submitBookingBtn"
                    >

                        <i class="bi bi-send me-1"></i>

                        Submit Request

                    </button>


                </div>


            </form>

        </div>

    </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| Date picker
|--------------------------------------------------------------------------
*/

const datePicker =
    document.getElementById('datePicker');


datePicker?.addEventListener(
    'change',
    function (event) {

        const selectedDate =
            event.target.value;


        if (!selectedDate) {
            return;
        }


        const d =
            new Date(
                selectedDate + 'T00:00:00'
            );


        /*
         * Wednesday = 3
         */
        if (d.getDay() !== 3) {

            alert(
                'Please select a Wednesday.'
            );


            event.target.value =
                '<?= e($date) ?>';

            return;
        }


        window.location.href =
            '?date=' +
            encodeURIComponent(selectedDate);

    }
);


/*
|--------------------------------------------------------------------------
| Purpose dropdown
|--------------------------------------------------------------------------
*/

const purposeSelect =
    document.getElementById('purpose');


const otherPurposeContainer =
    document.getElementById(
        'otherPurposeContainer'
    );


const otherPurposeInput =
    document.getElementById(
        'otherPurpose'
    );


function updatePurposeField() {

    if (!purposeSelect) {
        return;
    }


    if (purposeSelect.value === 'Other') {

        otherPurposeContainer.style.display =
            'block';

        otherPurposeInput.required =
            true;

    } else {

        otherPurposeContainer.style.display =
            'none';

        otherPurposeInput.required =
            false;

        otherPurposeInput.value =
            '';
    }

}


purposeSelect?.addEventListener(
    'change',
    updatePurposeField
);


updatePurposeField();


/*
|--------------------------------------------------------------------------
| Slot selection
|--------------------------------------------------------------------------
*/

function bindSlotButtons() {

    document
        .querySelectorAll('[data-slot]')
        .forEach(function (button) {


            button.addEventListener(
                'click',
                function () {


                    const slotId =
                        button.dataset.slot;


                    const startTime =
                        button.dataset.time;


                    const duration =
                        button.dataset.duration;


                    const slotInput =
                        document.getElementById(
                            'slotId'
                        );


                    const selectedTime =
                        document.getElementById(
                            'selectedTime'
                        );


                    const selectedDuration =
                        document.getElementById(
                            'selectedDuration'
                        );


                    if (slotInput) {

                        slotInput.value =
                            slotId;
                    }


                    if (selectedTime) {

                        selectedTime.textContent =
                            startTime;
                    }


                    if (selectedDuration) {

                        selectedDuration.textContent =
                            duration;
                    }

                }
            );

        });

}


bindSlotButtons();


/*
|--------------------------------------------------------------------------
| Prevent double submission
|--------------------------------------------------------------------------
*/

const bookingForm =
    document.getElementById(
        'bookingForm'
    );


bookingForm?.addEventListener(
    'submit',
    function () {


        const button =
            document.getElementById(
                'submitBookingBtn'
            );


        if (!button) {
            return;
        }


        button.disabled =
            true;


        button.innerHTML =
            '<span class="spinner-border spinner-border-sm me-1"></span>' +
            'Submitting...';

    }
);


/*
|--------------------------------------------------------------------------
| Live availability checking
|--------------------------------------------------------------------------
*/

let lastSignature = '';


async function syncAvailability() {

    try {

        const response =
            await fetch(
                '/api/availability?date=<?= rawurlencode($date) ?>',
                {
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );


        if (!response.ok) {
            return;
        }


        const data =
            await response.json();


        if (!data.ok) {
            return;
        }


        if (
            lastSignature &&
            data.signature !== lastSignature
        ) {

            window.location.reload();

            return;
        }


        lastSignature =
            data.signature;


    } catch (error) {

        console.warn(
            'Availability check failed:',
            error
        );

    }

}


/*
|--------------------------------------------------------------------------
| Check availability every 5 seconds
|--------------------------------------------------------------------------
*/

setInterval(
    syncAvailability,
    5000
);


syncAvailability();

</script>


<?php

require __DIR__ . '/../includes/footer.php';

?>