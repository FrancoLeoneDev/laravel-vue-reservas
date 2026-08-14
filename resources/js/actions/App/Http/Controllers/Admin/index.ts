import AgendaController from './AgendaController'
import ServiceController from './ServiceController'
import AvailabilityController from './AvailabilityController'
import BookingStatusController from './BookingStatusController'
const Admin = {
    AgendaController: Object.assign(AgendaController, AgendaController),
ServiceController: Object.assign(ServiceController, ServiceController),
AvailabilityController: Object.assign(AvailabilityController, AvailabilityController),
BookingStatusController: Object.assign(BookingStatusController, BookingStatusController),
}

export default Admin